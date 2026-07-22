<?php

namespace Modules\Schedule\Application\SubmitMonthlyAssignmentDossier;

use App\Http\Controllers\Controller;

class SubmitMonthlyAssignmentDossierController extends Controller
{
    public function __construct(
        private SubmitMonthlyAssignmentDossierHandler $handler
    ) {}

    /**
     * Submit a monthly assignment dossier for Phong Dao tao leadership review.
     */
    public function __invoke(SubmitMonthlyAssignmentDossierRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
