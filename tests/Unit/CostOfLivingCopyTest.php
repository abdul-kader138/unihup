<?php

namespace Tests\Unit;

use App\Support\CostOfLivingCopy;
use PHPUnit\Framework\TestCase;

class CostOfLivingCopyTest extends TestCase
{
    public function test_it_resolves_known_cities_including_english_italian_spelling_variants(): void
    {
        $milano = CostOfLivingCopy::forCity('Milano');
        $milan = CostOfLivingCopy::forCity('Milan');

        $this->assertSame(1338, $milano['rent']);
        $this->assertSame($milano, $milan);
    }

    public function test_it_returns_null_for_an_unknown_or_missing_city(): void
    {
        $this->assertNull(CostOfLivingCopy::forCity('Some Small Town'));
        $this->assertNull(CostOfLivingCopy::forCity(null));
        $this->assertNull(CostOfLivingCopy::forCity(''));
    }

    public function test_it_returns_null_for_a_city_alias_that_lacks_a_sourced_rent_figure(): void
    {
        // Padova is a known university city alias, but that edition of the
        // source only reported its rental yield, not a rent figure.
        $this->assertNull(CostOfLivingCopy::forCity('Padova'));
    }

    public function test_tiers_are_relative_to_the_national_average(): void
    {
        $this->assertSame('Well above national average', CostOfLivingCopy::forCity('Milano')['tier']);
        $this->assertSame('Below national average', CostOfLivingCopy::forCity('Messina')['tier']);
    }

    public function test_official_links_are_non_empty_urls(): void
    {
        $this->assertNotEmpty(CostOfLivingCopy::OFFICIAL_LINKS);

        foreach (CostOfLivingCopy::OFFICIAL_LINKS as $label => $url) {
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('#^https://#', $url);
        }
    }
}
