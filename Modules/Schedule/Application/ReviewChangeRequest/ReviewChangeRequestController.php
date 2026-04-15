<?php

namespace Modules\Schedule\Application\ReviewChangeRequest;

use App\Http\Controllers\Controller;

class ReviewChangeRequestController extends Controller
{
    public function __construct(
        private ReviewChangeRequestHandler $handler
    ) {}

    /**
     * Review a change request from departments.
     */
    public function __invoke(ReviewChangeRequestRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
