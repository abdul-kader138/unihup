<?php

namespace Tests\Unit;

use App\Support\Glossary;
use PHPUnit\Framework\TestCase;

class GlossaryTest extends TestCase
{
    public function test_all_entries_have_a_term_and_definition_and_are_sorted(): void
    {
        $all = Glossary::all();

        $this->assertNotEmpty($all);

        foreach ($all as $entry) {
            $this->assertArrayHasKey('key', $entry);
            $this->assertNotEmpty($entry['term']);
            $this->assertNotEmpty($entry['definition']);
        }

        $terms = array_map(fn ($e) => mb_strtolower($e['term']), $all);
        $sorted = $terms;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $terms);
    }

    public function test_get_and_definition_lookups(): void
    {
        $this->assertSame('ISEE', Glossary::get('isee')['term']);
        $this->assertStringContainsString('income', Glossary::definition('isee'));
        $this->assertNull(Glossary::get('not-a-term'));
        $this->assertNull(Glossary::definition('not-a-term'));
    }
}
