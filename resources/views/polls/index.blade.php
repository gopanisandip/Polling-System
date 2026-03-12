@extends('layouts.app')

@section('title', 'Active Polls - Poll System')

@section('content')
<div class="mb-4">
    <h1 class="h2 font-weight-bold">Active Polls</h1>
    <p class="text-muted">Check for active poll and add your vote</p>
</div>

@if($polls->isEmpty())
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h3 class="h5">No Active Polls</h3>
            <p class="text-muted">No active poll right now. Check later..</p>
        </div>
    </div>
@else
    <div class="row">
        @foreach($polls as $poll)
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="{{ route('polls.show', $poll->slug) }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge {{ $poll->isOpen() ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $poll->isOpen() ? 'Active' : 'Closed' }}
                                </span>
                                <small class="text-muted">{{ $poll->created_at->diffForHumans() }}</small>
                            </div>

                            <h5 class="card-title text-dark font-weight-bold mb-2">{{ $poll->title }}</h5>

                            @if($poll->description)
                                <p class="text-muted small text-truncate mb-3">{{ $poll->description }}</p>
                            @endif

                            <div class="d-flex justify-content-between border-top pt-3 mt-auto">
                                <small class="text-muted">
                                    <i class="fas fa-list mr-1"></i> {{ $poll->options_count }} options
                                </small>
                                <small class="text-muted" id="poll-votes-{{ $poll->id }}">
                                    <i class="fas fa-user mr-1"></i> <span class="vote-count">{{ $poll->total_votes }}</span> votes
                                </small>
                            </div>

                            @if($poll->end_at)
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="far fa-clock mr-1"></i> Ends {{ $poll->end_at->diffForHumans() }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $polls->links() }}
    </div>
@endif
@push('scripts')
<script>
$(function() {
    if (typeof window.socketIO === 'undefined') return;

    var pollIds = @json($polls->pluck('id'));

    pollIds.forEach(function(id) {
        window.socketIO.emit('subscribe', 'poll.' + id);
    });

    window.socketIO.on('vote.recorded', function(data) {
        var el = $('#poll-votes-' + data.poll_id + ' .vote-count');
        if (el.length) {
            el.text(data.total_votes);
        }
    });
});
</script>
@endpush
@endsection
