<?php

namespace App\Http\Controllers\Dev;

use App\Domain\Sacred\SacredInspectionService;
use App\Http\Controllers\Controller;
use App\Models\Miracle;
use App\Models\PilgrimageRoute;
use App\Models\Relic;
use App\Models\Saint;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SacredInspectController extends Controller
{
    public function __construct(private SacredInspectionService $inspection)
    {
    }

    public function saint(Request $request, Saint $saint)
    {
        $this->assertLocal($request);

        return view('dev.inspect.sacred-saint', [
            'saint' => $saint,
            'payload' => $this->inspection->inspectSaint($saint),
        ]);
    }

    public function relic(Request $request, Relic $relic)
    {
        $this->assertLocal($request);

        return view('dev.inspect.sacred-relic', [
            'relic' => $relic,
            'payload' => $this->inspection->inspectRelic($relic),
        ]);
    }

    public function route(Request $request, PilgrimageRoute $route)
    {
        $this->assertLocal($request);

        return view('dev.inspect.sacred-route', [
            'route' => $route,
            'payload' => $this->inspection->inspectRoute($route),
        ]);
    }

    public function miracle(Request $request, Miracle $miracle)
    {
        $this->assertLocal($request);

        return view('dev.inspect.sacred-miracle', [
            'miracle' => $miracle,
            'payload' => $this->inspection->inspectMiracle($miracle),
        ]);
    }

    private function assertLocal(Request $request): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new AccessDeniedHttpException('Sacred inspect is local/testing only.');
        }
    }
}
