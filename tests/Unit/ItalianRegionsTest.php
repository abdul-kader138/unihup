<?php

namespace Tests\Unit;

use App\Support\ItalianRegions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ItalianRegionsTest extends TestCase
{
    #[DataProvider('synonymProvider')]
    public function test_it_reconciles_english_and_italian_spellings_to_the_same_key(string $a, string $b): void
    {
        $this->assertSame(ItalianRegions::canonicalize($a), ItalianRegions::canonicalize($b));
    }

    public static function synonymProvider(): array
    {
        return [
            ['Lombardy', 'Lombardia'],
            ['Piedmont', 'Piemonte'],
            ['Tuscany', 'Toscana'],
            ['Apulia', 'Puglia'],
            ['Emilia Romagna', 'Emilia-Romagna'],
            ['Friuli Venezia Giulia', 'Friuli-Venezia Giulia'],
            ["Valle D'aosta", "Valle d'Aosta"],
            ['Provincia Autonoma Di Bolzano', 'Trentino-Alto Adige'],
            ['Provincia Autonoma Di Trento', 'Trentino-Alto Adige'],
        ];
    }

    public function test_it_returns_null_for_blank_input(): void
    {
        $this->assertNull(ItalianRegions::canonicalize(null));
        $this->assertNull(ItalianRegions::canonicalize(''));
    }
}
