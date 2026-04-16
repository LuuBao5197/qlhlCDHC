<?php
namespace Modules\Schedule\Application\GetScheduleSemester;
use App\Http\Controllers\Controller;

class GetScheduleSemesterController extends Controller
{
    public function __construct(
        private GetScheduleSemesterHandler $handler
    ) {}

    /**
     * Display semester schedule.
     */
    public function __invoke(GetScheduleSemesterRequest $request)
    {
        return $this->handler->handle($request);
    }
}
?>
