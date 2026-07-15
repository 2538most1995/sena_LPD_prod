<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class PdfReportRenderingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, int}>
     */
    public static function reportRoutes(): array
    {
        $routes = [];

        for ($document = 0; $document < 23; $document++) {
            $routes['pt-'.($document + 1)] = ['pt', $document];
        }

        foreach ([
            ['open', 0], ['open', 1], ['open', 2], ['open', 3],
            ['time', 0], ['time', 1], ['time', 2], ['time', 3],
            ['finance', 0], ['finance', 1], ['survey', 0],
            ['result', 0], ['result', 1], ['result', 2],
            ['photo', 0], ['photo', 1],
        ] as [$type, $document]) {
            $routes[$type.'-'.$document] = [$type, $document];
        }

        return $routes;
    }

    #[DataProvider('reportRoutes')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_every_report_route_returns_a_valid_pdf(string $type, int $document): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/v1/reports/open?'.http_build_query([
            'type' => $type,
            'doc' => $document,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }
}
