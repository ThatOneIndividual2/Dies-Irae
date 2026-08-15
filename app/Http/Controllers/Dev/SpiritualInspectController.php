<?php

namespace App\Http\Controllers\Dev;

use App\Domain\Spiritual\SpiritualInspectionService;
use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Policies\SpiritualStatePolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SpiritualInspectController extends Controller
{
    public function __construct(private SpiritualInspectionService $inspection)
    {
    }

    public function character(Request $request, Character $character, SpiritualStatePolicy $policy)
    {
        $this->assertInspect($request, $policy, $character);
        $payload = $this->inspection->inspectCharacter($character);

        return view('dev.inspect.spiritual-character', [
            'character' => $character,
            'payload' => $payload,
        ]);
    }

    public function subject(Request $request, string $type, int $id, SpiritualStatePolicy $policy)
    {
        $this->assertInspect($request, $policy, null);
        $payload = $this->inspection->inspectSubject($type, $id);

        return view('dev.inspect.spiritual-subject', [
            'payload' => $payload,
        ]);
    }

    private function assertInspect(Request $request, SpiritualStatePolicy $policy, ?Character $character): void
    {
        if (!$policy->inspectAdmin($request->user(), $character)) {
            throw new AccessDeniedHttpException('Spiritual inspect is local/testing only.');
        }
    }
}
