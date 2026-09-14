<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\PHPStan;

use GianTiaga\SpiralOpenApi\PHPStan\Rules\RequireOpenApiTagRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequireOpenApiTagRule>
 */
final class RequireOpenApiTagRuleTest extends RuleTestCase
{
    private bool $required = true;
    public function testAllowsTaggedRouteIgnoredRouteAndMethodWithoutRoute(): void
    {
        $file = __DIR__ . '/Fixtures/RequireOpenApiTagValid.fixture';
        require_once $file;
        $this->analyse(files: [$file], expectedErrors: []);
    }
    public function testRejectsEveryRouteWithoutTag(): void
    {
        $file = __DIR__ . '/Fixtures/RequireOpenApiTagInvalid.fixture';
        require_once $file;
        $errors = $this->gatherAnalyserErrors([$file]);
        self::assertSame(['gianTiaga.spiralOpenApi.routeTagRequired', 'gianTiaga.spiralOpenApi.routeTagRequired'], \array_map(callback: static fn($error): string|null => $error->getIdentifier(), array: $errors));
    }
    public function testDisabledRuleRequiresNothing(): void
    {
        $this->required = false;
        $file = __DIR__ . '/Fixtures/RequireOpenApiTagInvalid.fixture';
        require_once $file;
        $this->analyse(files: [$file], expectedErrors: []);
    }
    protected function getRule(): Rule
    {
        return new RequireOpenApiTagRule(required: $this->required);
    }
}
