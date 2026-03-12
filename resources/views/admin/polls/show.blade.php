@extends('layouts.app')

@section('title', $poll->title . ' - Results')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('admin.polls.index') }}" class="text-primary small">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <div>
                <a href="{{ route('admin.polls.edit', $poll) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge {{ $poll->isOpen() ? 'badge-success' : 'badge-danger' }} px-3 py-2">
                        {{ $poll->isOpen() ? 'Active' : 'Closed' }}
                    </span>
                </div>

                <h1 class="h3 font-weight-bold mb-2">{{ $poll->title }}</h1>

                @if($poll->description)
                    <p class="text-muted mb-3">{{ $poll->description }}</p>
                @endif

                <div class="small text-muted">
                    <span class="mr-3">Created {{ $poll->created_at->format('M d, Y') }}</span>
                    @if($poll->end_at)
                        <span>{{ $poll->end_at->isPast() ? 'Ended' : 'Ends' }} {{ $poll->end_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="admin-results" data-poll-id="{{ $poll->id }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 font-weight-bold mb-0">Live</h2>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-success rounded-circle d-inline-block mr-2" style="width:8px;height:8px;padding:0"></span>
                        <span class="small text-success mr-3">Real-time</span>
                        <span class="h4 font-weight-bold text-primary mb-0 mr-2" id="admin-total-votes">{{ $poll->total_votes }}</span>
                        <span class="text-muted small">total votes</span>
                    </div>
                </div>

                <div id="admin-results-list">
                    @foreach($poll->options as $option)
                        @php
                            $pct = $poll->total_votes > 0 ? round(($option->votes_count / $poll->total_votes) * 100, 1) : 0;
                        @endphp
                        <div class="result-item mb-4" data-option-id="{{ $option->id }}">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="font-weight-bold">{{ $option->text }}</span>
                                <div>
                                    <strong class="votes-count">{{ $option->votes_count }}</strong>
                                    <span class="text-muted small ml-1">(<span class="votes-pct">{{ $pct }}</span>%)</span>
                                </div>
                            </div>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-primary" role="progressbar"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="font-weight-bold mb-3">Share this Poll</h6>
                <div class="input-group">
                    <input type="text" readonly value="{{ $poll->getShareUrl() }}" id="share-url" class="form-control bg-light">
                    <div class="input-group-append">
                        <button onclick="copyShareLink()" id="copy-btn" class="btn btn-primary">
                            Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    var pollId = {{ $poll->id }};

    if (typeof window.socketIO !== 'undefined') {
        window.socketIO.emit('subscribe', 'poll.' + pollId);
        window.socketIO.on('vote.recorded', function(data) {
            if (data.poll_id === pollId) {
                $('#admin-total-votes').text(data.total_votes);
                data.results.forEach(function(option) {
                    var $item = $('.result-item[data-option-id="' + option.id + '"]');
                    if ($item.length) {
                        $item.find('.votes-count').text(option.votes_count);
                        $item.find('.votes-pct').text(option.percentage);
                        $item.find('.progress-bar').css('width', option.percentage + '%');
                    }
                });
            }
        });
    }

});


function copyShareLink() {

    var url = document.getElementById('share-url').value;

    navigator.clipboard.writeText(url).then(function() {

        var btn = document.getElementById('copy-btn');
        btn.textContent = 'Copied!';
        btn.className = 'btn btn-success';

        setTimeout(function() {

            btn.textContent = 'Copy Link';
            btn.className = 'btn btn-primary';

        }, 2000);
    });
}
</script>
@endpush
@endsection
