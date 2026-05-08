<?php

namespace Modules\Schedule\Application\CreateChangeRequest;

use App\Http\Controllers\Controller;

class CreateChangeRequestController extends Controller
{
    public function __construct(
        private CreateChangeRequestHandler $handler
    ) {}

    /**
     * Create a new schedule change request from department workflow.
     */
    public function __invoke(CreateChangeRequestRequest $request)
    {
        return $this->handler->handle($request);
    }
}
