<?php

use App\Support\DocumentNumberService;

beforeEach(function () {
    $this->docService = app(DocumentNumberService::class);
});

test('TEST 10: document number generates expected format and sequential increment', function () {
    $num1 = $this->docService->generate('PB', 2026);
    $num2 = $this->docService->generate('PB', 2026);
    $num3 = $this->docService->generate('PB', 2026);

    expect($num1)->toEqual('PB-2026-000001')
        ->and($num2)->toEqual('PB-2026-000002')
        ->and($num3)->toEqual('PB-2026-000003');
});

test('TEST 11: different document types have independent sequences', function () {
    $pb1 = $this->docService->generate('PB', 2026);
    $pj1 = $this->docService->generate('PJ', 2026);
    $tr1 = $this->docService->generate('TR', 2026);
    $pb2 = $this->docService->generate('PB', 2026);

    expect($pb1)->toEqual('PB-2026-000001')
        ->and($pj1)->toEqual('PJ-2026-000001')
        ->and($tr1)->toEqual('TR-2026-000001')
        ->and($pb2)->toEqual('PB-2026-000002');
});

test('TEST 12: different years have independent sequences', function () {
    $num2026 = $this->docService->generate('PB', 2026);
    $num2027 = $this->docService->generate('PB', 2027);

    expect($num2026)->toEqual('PB-2026-000001')
        ->and($num2027)->toEqual('PB-2027-000001');
});

test('TEST 13: document sequence is unique and handles case-insensitivity cleanly', function () {
    $lower = $this->docService->generate('pb', 2026);
    $upper = $this->docService->generate('PB', 2026);

    expect($lower)->toEqual('PB-2026-000001')
        ->and($upper)->toEqual('PB-2026-000002');
});
