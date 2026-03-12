@extends('layouts.app')

@section('title', 'My Polls - Admin Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 font-weight-bold mb-1">My Polls</h1>
        <p class="text-muted mb-0">Manage your polls and view results</p>
    </div>
    <a href="{{ route('admin.polls.create') }}" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Create New Poll
    </a>
</div>

@if($polls->isEmpty())
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
            <h3 class="h5">No Polls Yet</h3>
            <p class="text-muted">Create your poll!</p>
        </div>
    </div>
@else
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Poll</th>
                        <th>Status</th>
                        <th class="text-center">Votes</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($polls as $poll)
                        <tr id="poll-row-{{ $poll->id }}">
                            <td>
                                <a href="{{ route('admin.polls.show', $poll) }}" class="font-weight-bold text-dark">
                                    {{ $poll->title }}
                                </a>
                                @if($poll->description)
                                    <p class="text-muted small mb-0 text-truncate" style="max-width: 300px;">{{ $poll->description }}</p>
                                @endif
                            </td>
                            <td>
                                @if($poll->is_active && (!$poll->end_at || $poll->end_at->isFuture()))
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Closed</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <strong id="poll-votes-{{ $poll->id }}">{{ $poll->total_votes }}</strong>
                            </td>
                            <td class="text-muted small">
                                {{ $poll->created_at->format('M d, Y') }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('polls.show', $poll->slug) }}" target="_blank" class="btn btn-link btn-sm p-1 text-info" title="View Public Poll">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="{{ route('admin.polls.edit', $poll) }}" class="btn btn-link btn-sm p-1 text-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deletePoll({{ $poll->id }})" class="btn btn-link btn-sm p-1 text-danger" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $polls->links() }}
    </div>
@endif

@push('scripts')
<script>
$(function() {
    if (typeof window.socketIO !== 'undefined') {
        $('[id^="poll-votes-"]').each(function() {
            var pollId = parseInt(this.id.replace('poll-votes-', ''));
            window.socketIO.emit('subscribe', 'poll.' + pollId);
        });

        window.socketIO.on('vote.recorded', function(data) {
            var $el = $('#poll-votes-' + data.poll_id);
            if ($el.length) {
                $el.text(data.total_votes);
            }
        });
    }

});

function deletePoll(pollId) {

    if (!confirm('Are you sure you want to delete this poll?')){
        return
    };

    $.ajax({
        url: '/admin/polls/' + pollId,
        method: 'DELETE',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                $('#poll-row-' + pollId).fadeOut(300, function() { $(this).remove(); });
                showToast(response.message, 'success');
            }
        },
        error: function() {
            showToast('Failed to delete poll', 'error');
        }
    });
}
</script>
@endpush
@endsection
