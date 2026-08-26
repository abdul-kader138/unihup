<?php

namespace Tests\Unit;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Services\Universities\MurUstatImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MurUstatImporterTest extends TestCase
{
    use RefreshDatabase;

    private function fakeMurResponses(): void
    {
        $atenei = "COD_Ateneo;NomeEsteso;NomeOperativo;Status;Descrizione;StataleLibera;Indirizzo;CITTA;PROVINCIA;REGIONE;NOME_REGIONE_MACRO\n"
            ."00101;Università degli studi di Torino;Torino;A;Università;S;Via Verdi, 8;TORINO;TORINO;PIEMONTE;NORD-OVEST\n"
            ."99999;Ateneo Chiuso;Chiuso;M;Università;S;;ROMA;ROMA;LAZIO;CENTRO\n";

        $corsi = "ANNO_VALIDITA;NomeOperativo;Area;Gruppo_Nome;NUMERO;DES;NOME_CORSO;PROVINCIA;COMUNE;ACCESSO;DIDATTICA\n"
            ."2023;Torino;STEM;Ingegneria industriale;L-9;Ingegneria meccanica;Vecchio Corso;TORINO;TORINO;accesso libero;in presenza\n"
            ."2024;Torino;STEM;Ingegneria industriale;L-9;Ingegneria meccanica;Ingegneria Meccanica;TORINO;TORINO;accesso libero;in presenza\n"
            ."2024;Torino;STEM;Ingegneria industriale;LM-33;Ingegneria meccanica;Ingegneria Meccanica Magistrale;TORINO;TORINO;accesso libero;in presenza\n"
            ."2024;Torino;Giuridica;Giurisprudenza;LMG/01;Scienze giuridiche;Giurisprudenza;TORINO;TORINO;nazionale;in presenza\n"
            ."2024;Sconosciuto;STEM;Informatica;L-31;Informatica;Corso Fantasma;ROMA;ROMA;accesso libero;in presenza\n";

        Http::fake([
            '*atenei.csv' => Http::response($atenei, 200),
            '*corsidilaurea*' => Http::response($corsi, 200),
        ]);
    }

    public function test_it_imports_only_the_target_year_and_maps_degree_classes(): void
    {
        $this->fakeMurResponses();

        $result = (new MurUstatImporter(2024))->import();

        $this->assertSame(1, $result->universities); // "Chiuso" (Status=M) and "Sconosciuto" (unmatched) excluded
        $this->assertSame(3, $result->degreePrograms); // the 2023 row is excluded by year

        $university = University::where('slug', 'universita-degli-studi-di-torino')->first();
        $this->assertNotNull($university);
        $this->assertSame('Torino', $university->city);
        $this->assertSame('Piemonte', $university->region);

        $bachelor = DegreeProgram::where('name', 'Ingegneria Meccanica')->firstOrFail();
        $this->assertSame('bachelor', $bachelor->degree_level);
        $this->assertSame(3, $bachelor->duration_years);
        $this->assertSame('open', $bachelor->admission_type);

        $master = DegreeProgram::where('name', 'Ingegneria Meccanica Magistrale')->firstOrFail();
        $this->assertSame('master', $master->degree_level);
        $this->assertSame(2, $master->duration_years);

        $law = DegreeProgram::where('name', 'Giurisprudenza')->firstOrFail();
        $this->assertSame('bachelor', $law->degree_level); // single-cycle, per DegreeProgramSeeder's convention
        $this->assertSame(5, $law->duration_years);
        $this->assertSame('restricted', $law->admission_type);

        $this->assertFalse(DegreeProgram::where('name', 'Corso Fantasma')->exists());
        $this->assertFalse(DegreeProgram::where('name', 'Vecchio Corso')->exists());
    }

    public function test_re_running_the_import_is_idempotent(): void
    {
        $this->fakeMurResponses();

        (new MurUstatImporter(2024))->import();
        $second = (new MurUstatImporter(2024))->import();

        $this->assertSame(1, $second->universities);
        $this->assertSame(3, $second->degreePrograms);
        $this->assertSame(1, University::count());
        $this->assertSame(2, Subject::count());
        $this->assertSame(3, DegreeProgram::count());
    }
}
