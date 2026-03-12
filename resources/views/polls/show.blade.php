@extends('layouts.app')

@section('title', $poll->title . ' - Poll System')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge {{ $poll->isOpen() ? 'badge-success' : 'badge-danger' }} px-3 py-2">
                        {{ $poll->isOpen() ? 'Active' : 'Votting Ended' }}
                    </span>
                    <span class="text-muted small" id="header-votes-display">{{ $poll->total_votes }} vote{{ $poll->total_votes !== 1 ? 's' : '' }}</span>
                </div>

                <h1 class="h3 font-weight-bold mb-2">{{ $poll->title }}</h1>

                @if($poll->description)
                    <p class="text-muted mb-3">{{ $poll->description }}</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="poll-container" data-poll-id="{{ $poll->id }}">
            <div class="card-body">
                <div id="voting-form" class="{{ $hasVoted || !$poll->isOpen() ? 'd-none' : '' }}">
                    <h5 class="font-weight-bold mb-3">Add your Poll</h5>
                    <div id="options-list">
                        @foreach($poll->options as $option)
                            <label class="d-flex align-items-center p-3 mb-2 border rounded" data-option-id="{{ $option->id }}" style="cursor:pointer">
                                <input type="radio" name="option_id" value="{{ $option->id }}" class="mr-3">
                                <span class="font-weight-medium">{{ $option->text }}</span>
                            </label>
                        @endforeach
                    </div>

                    <button id="vote-btn" disabled class="btn btn-primary btn-block btn-lg mt-3">
                        <span id="vote-btn-text">Select an option to vote</span>
                        <span id="vote-spinner" class="d-none">
                            <i class="fas fa-spinner fa-spin ml-2"></i>
                        </span>
                    </button>
                </div>

                <div id="results-section" class="{{ !$hasVoted && $poll->isOpen() ? 'd-none' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="font-weight-bold mb-0">Results</h5>
                        <span class="text-muted small" id="total-votes-display">
                            {{ $poll->total_votes }} total vote{{ $poll->total_votes !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <div id="results-list">
                        @foreach($poll->options as $option)
                            @php
                                $pct = $poll->total_votes > 0 ? round(($option->votes_count / $poll->total_votes) * 100, 1) : 0;
                            @endphp
                            <div class="result-item mb-3" data-option-id="{{ $option->id }}">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="font-weight-medium small">
                                        {{ $option->text }}
                                        @if($votedOptionId === $option->id)
                                            <i class="fas fa-check-circle text-primary ml-1 voted-check"></i>
                                        @endif
                                    </span>
                                    <span class="text-muted small">
                                        <span class="votes-count">{{ $option->votes_count }}</span> votes (<span class="votes-pct">{{ $pct }}</span>%)
                                    </span>
                                </div>
                                <div class="progress" style="height: 12px;">
                                    <div class="progress-bar {{ $votedOptionId === $option->id ? 'bg-primary' : 'bg-info' }}"
                                         role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($hasVoted)
                        <p class="small text-muted text-center mt-4 mb-0">
                            <i class="fas fa-check mr-1"></i> You have already voted on this poll
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
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

        <div class="text-center">
            <a href="{{ route('home') }}" class="text-primary">
                <i class="fas fa-arrow-left mr-1"></i> View all polls
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {


    var pollId = {{ $poll->id }};
    var hasVoted = {{ $hasVoted ? 'true' : 'false' }};
    var votedOptionId = {{ $votedOptionId ?? 'null' }};
    var selectedOption = null;

    $('input[name="option_id"]').on('change', function() {

        selectedOption = $(this).val();
        $('#vote-btn').prop('disabled', false);
        $('#vote-btn-text').text('Submit Vote');

        $('.poll-option').removeClass('border-primary bg-light');
        $(this).closest('.poll-option').addClass('border-primary bg-light');

    });

    $('#vote-btn').on('click', function() {

        if (!selectedOption){
            return;
        };

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#vote-btn-text').text('Submitting...');
        $('#vote-spinner').removeClass('d-none');

        $.ajax({
            url: '/poll/' + pollId + '/vote',
            method: 'POST',
            data: {
                option_id: selectedOption,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {

                if (response.success) {

                    hasVoted = true;
                    votedOptionId = response.voted_option_id;
                    updateResults(response.results, response.total_votes);
                    $('#voting-form').addClass('d-none');
                    $('#results-section').removeClass('d-none');
                    showToast(response.message, 'success');
                }
            },
            error: function(xhr) {

                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                showToast(msg, 'error');
                $btn.prop('disabled', false);
                $('#vote-btn-text').text('Submit Vote');
                $('#vote-spinner').addClass('d-none');
            }
        });
    });

    if (typeof window.socketIO !== 'undefined') {

        window.socketIO.emit('subscribe', 'poll.' + pollId);

        window.socketIO.on('vote.recorded', function(data) {

            if (data.poll_id === pollId) {

                updateResults(data.results, data.total_votes);
            }
        });
    }

    function updateResults(results, totalVotes) {

        var voteLabel = totalVotes + ' total vote' + (totalVotes !== 1 ? 's' : '');
        $('#total-votes-display').text(voteLabel);
        $('#header-votes-display').text(totalVotes + ' vote' + (totalVotes !== 1 ? 's' : ''));

        results.forEach(function(option) {

            var $item = $('.result-item[data-option-id="' + option.id + '"]');

            if ($item.length) {

                $item.find('.votes-count').text(option.votes_count);
                $item.find('.votes-pct').text(option.percentage);
                $item.find('.progress-bar').css('width', option.percentage + '%');

                if (option.id === votedOptionId) {

                    if (!$item.find('.voted-check').length) {

                        $item.find('.font-weight-medium').append(
                            ' <i class="fas fa-check-circle text-primary ml-1 voted-check"></i>'
                        );
                    }

                    $item.find('.progress-bar').removeClass('bg-info').addClass('bg-primary');
                }
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
