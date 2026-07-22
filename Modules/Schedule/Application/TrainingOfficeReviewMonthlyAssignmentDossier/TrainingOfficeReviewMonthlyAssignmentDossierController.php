<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewMonthlyAssignmentDossier;

use App\Http\Controllers\Controller;

class TrainingOfficeReviewMonthlyAssignmentDossierController extends Controller
{
    public function __construct(
        private TrainingOfficeReviewMonthlyAssignmentDossierHandler $handler
    ) {}

    /**
     * Handle Phong Dao tao leadership review of a monthly assignment dossier submitted by staff.
     */
    public function __invoke(TrainingOfficeReviewMonthlyAssignmentDossierRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
