<?php

namespace App\Jobs;

use App\Domain\Compliance\Actions\ProcessDataRequest;
use App\Domain\Compliance\Models\DataRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportUserData implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $requestId) {}

    public function handle(ProcessDataRequest $action): void
    {
        $request = DataRequest::find($this->requestId);
        if ($request && $request->status === 'pending') {
            $action->export($request);
        }
    }
}
