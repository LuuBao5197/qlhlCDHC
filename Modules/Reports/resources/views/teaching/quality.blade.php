@extends('layouts.dashboard')

@section('title', 'Chất lượng giảng dạy')

@section('content')
    @include('reports::teaching.partials.nav')
    @include('reports::teaching.partials.filters', ['showRatingFilter' => true])

    <div class="row">
        @foreach([
            'Tổng lượt lớp được đánh giá' => number_format($data['cards']['quality_turns']),
            'Tốt' => number_format($data['cards']['tot']),
            'Khá' => number_format($data['cards']['kha']),
            'Trung bình' => number_format($data['cards']['trung_binh']),
            'Yếu' => number_format($data['cards']['yeu']),
        ] as $label => $value)
            <div class="col-xl-2 col-sm-6 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted font-weight-normal">{{ $label }}</h6>
                        <h3 class="mb-0">{{ $value }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Tỷ lệ từng mức xếp loại</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            @foreach($ratingLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @include('reports::teaching.partials.rating-cells', ['ratings' => $data['ratingDistribution']])
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Danh sách tiết cần kiểm tra</h4>
            @include('reports::teaching.partials.quality-details-table', ['details' => $data['details']])
        </div>
    </div>
@endsection
