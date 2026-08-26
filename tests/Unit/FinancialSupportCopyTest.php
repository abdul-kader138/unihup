<?php

namespace Tests\Unit;

use App\Support\FinancialSupportCopy;
use PHPUnit\Framework\TestCase;

class FinancialSupportCopyTest extends TestCase
{
    public function test_it_exposes_isee_parificato_guidance_mentioning_the_caf_and_deadline_consequence(): void
    {
        $this->assertStringContainsString('CAF', FinancialSupportCopy::ISEE_PARIFICATO_NOTE);
        $this->assertStringContainsString('maximum fee bracket', FinancialSupportCopy::ISEE_PARIFICATO_NOTE);
    }

    public function test_it_exposes_maeci_scholarship_guidance_distinct_from_regional_scholarships(): void
    {
        $this->assertStringContainsString('MAECI', FinancialSupportCopy::MAECI_SCHOLARSHIP_NOTE);
        $this->assertStringContainsString('regional', FinancialSupportCopy::MAECI_SCHOLARSHIP_NOTE);
    }

    public function test_official_links_are_non_empty_urls(): void
    {
        $this->assertNotEmpty(FinancialSupportCopy::OFFICIAL_LINKS);

        foreach (FinancialSupportCopy::OFFICIAL_LINKS as $label => $url) {
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('#^https://#', $url);
        }
    }
}
