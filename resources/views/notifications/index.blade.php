@extends('layouts.dashboard')

@section('title', 'Thong bao noi bo')

@section('content')
    @php
        $items = $notifications->getCollection();
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Thong bao noi bo</h4>
                        <p class="text-muted mb-0">Thong bao moi nhat trong he thong duoc luu trong database.</p>
                    </div>

                    <form method="POST" action="{{ route('notifications.read-all') }}" class="mt-3 mt-md-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary" @disabled($unreadCount === 0)>
                            Danh dau tat ca da doc
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if ($items->isEmpty())
        <div class="card mt-3">
            <div class="card-body text-center text-muted py-5">
                Chua co thong bao nao.
            </div>
        </div>
    @else
        <div class="card mt-3">
            <div class="card-body p-0">
                <div class="preview-list">
                    @foreach ($items as $notification)
                        <a class="dropdown-item preview-item d-block py-3 {{ $notification['is_read'] ? '' : 'bg-light' }}"
                           href="{{ route('notifications.show', $notification['id']) }}">
                            <div class="d-flex align-items-start">
                                <div class="preview-thumbnail mr-3">
                                    <div class="preview-icon bg-dark rounded-circle">
                                        <i class="mdi {{ $notification['icon'] }} {{ $notification['icon_color_class'] }}"></i>
                                    </div>
                                </div>
                                <div class="preview-item-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="preview-subject mb-1 font-weight-semibold">
                                                {{ $notification['title'] }}
                                                @if (! $notification['is_read'])
                                                    <span class="badge badge-danger ml-2">Moi</span>
                                                @endif
                                            </p>
                                            <p class="text-muted mb-1">{{ $notification['excerpt'] }}</p>
                                            <p class="text-muted mb-0 small">{{ $notification['type_label'] }}</p>
                                        </div>
                                        <div class="text-right ml-3">
                                            <p class="text-muted mb-0 small">{{ $notification['created_at_human'] }}</p>
                                            <p class="text-muted mb-0 small">
                                                {{ optional($notification['created_at'])->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @if (! $loop->last)
                            <div class="dropdown-divider m-0"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
