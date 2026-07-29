<?php

namespace Modules\Training\Application\Management\SubjectLessons;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageSubjectLessonsController extends Controller
{
    public function __construct(
        private ManageSubjectLessonsHandler $handler,
        private ImportSubjectLessonsHandler $importHandler
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->handler->index($request);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->handler->store($request);
    }

    public function show(int $id): JsonResponse
    {
        return $this->handler->show($id);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->handler->update($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->handler->destroy($id);
    }

    public function import(ImportSubjectLessonsRequest $request): JsonResponse
    {
        return $this->importHandler->handle($request);
    }

    public function downloadImportTemplate(): Response
    {
        $content = "\xEF\xBB\xBFsubject_code,code,name,expected_periods,note\r\n"
            . "MH-001,1,Giới thiệu môn học,2,\r\n"
            . "MH-001,2,Các khái niệm cơ bản,3,\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="subject-lesson-import-template.csv"',
        ]);
    }
}