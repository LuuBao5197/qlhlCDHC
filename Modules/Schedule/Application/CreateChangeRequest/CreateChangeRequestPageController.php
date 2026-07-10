<?php

namespace Modules\Schedule\Application\CreateChangeRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Schedule\Application\Shared\ChangeRequestPageDataBuilder;

class CreateChangeRequestPageController extends Controller
{
    public function __construct(
        private ChangeRequestPageDataBuilder $dataBuilder
    ) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isDepartmentStaff() || $user->isTrainingOffice() || $user->isAdmin()),
            403
        );

        return response()->view('schedule::change-request.create', $this->dataBuilder->build($user));
    }
}
