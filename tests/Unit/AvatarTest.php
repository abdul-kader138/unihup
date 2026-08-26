<?php

namespace Tests\Unit;

use App\Support\Avatar;
use PHPUnit\Framework\TestCase;

class AvatarTest extends TestCase
{
    public function test_it_produces_an_svg_data_uri_containing_initials_from_the_meaningful_words(): void
    {
        $uri = Avatar::initialsDataUri('Università degli studi di Bologna');

        $this->assertStringStartsWith('data:image/svg+xml,', $uri);

        $svg = rawurldecode(substr($uri, strlen('data:image/svg+xml,')));
        $this->assertStringContainsString('>B<', $svg); // "Bologna" — stopwords stripped
    }

    public function test_it_takes_up_to_two_meaningful_words(): void
    {
        $svg = rawurldecode(substr(Avatar::initialsDataUri('Politecnico di Milano'), strlen('data:image/svg+xml,')));

        $this->assertStringContainsString('>PM<', $svg);
    }

    public function test_the_same_name_always_produces_the_same_color(): void
    {
        $first = Avatar::initialsDataUri('Bocconi University');
        $second = Avatar::initialsDataUri('Bocconi University');

        $this->assertSame($first, $second);
    }

    public function test_it_never_produces_a_blank_monogram(): void
    {
        $svg = rawurldecode(substr(Avatar::initialsDataUri('Di Del La'), strlen('data:image/svg+xml,')));

        // every word here is a stopword — must fall back to the raw words, not blank text
        $this->assertMatchesRegularExpression('/>[A-Z?]{1,2}</', $svg);
    }
}
