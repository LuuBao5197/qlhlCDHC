<?php

namespace Modules\Schedule\Application\LeadershipReviewMonthlyAssignmentDossier;

use App\Http\Controllers\Controller;

class LeadershipReviewMonthlyAssignmentDossierController extends Controller
{
    public function __construct(
        private LeadershipReviewMonthlyAssignmentDossierHandler $handler
    ) {}

    /**
     * Handle leadership (Ban Giam hieu) review of a submitted monthly assignment dossier.
     */
    public function __invoke(LeadershipReviewMonthlyAssignmentDossierRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
