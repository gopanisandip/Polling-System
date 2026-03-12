@extends('layouts.app')

@section('title', 'Edit: ' . $poll->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-3">
            <a href="{{ route('admin.polls.show', $poll) }}" class="text-primary small">
                <i class="fas fa-arrow-left mr-1"></i> Back to Results
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 font-weight-bold mb-4">Edit Poll</h1>

                <form id="edit-poll-form">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="title">Poll Question / Title</label>
                        <input type="text" name="title" id="title" value="{{ $poll->title }}" required class="form-control">
                        <div class="text-danger small mt-1 error-msg" data-field="title"></div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control">{{ $poll->description }}</textarea>
                        <div class="text-danger small mt-1 error-msg" data-field="description"></div>
                    </div>

                    <div class="form-group">
                        <label>Current Options</label>
                        @foreach($poll->options as $option)
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light border rounded mb-2">
                                <span class="font-weight-medium">{{ $option->text }}</span>
                                <span class="badge badge-secondary">{{ $option->votes_count }} votes</span>
                            </div>
                        @endforeach
                        <small class="form-text text-muted">Options cannot be modified after creation to preserve vote integrity.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active" {{ $poll->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active (accepting votes)</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="end_at">End Date</label>
                        <input type="datetime-local" name="end_at" id="end_at" class="form-control"
                            value="{{ $poll->end_at ? $poll->end_at->format('Y-m-d\TH:i') : '' }}">
                        <div class="text-danger small mt-1 error-msg" data-field="end_at"></div>
                    </div>

                    <hr>
                    <button type="submit" id="submit-btn" class="btn btn-primary btn-block btn-lg">
                        <span id="submit-text">Update Poll</span>
                        <span id="submit-spinner" class="d-none">
                            <i class="fas fa-spinner fa-spin ml-2"></i>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    $('#edit-poll-form').on('submit', function(e) {
        e.preventDefault();
        $('.error-msg').text('');

        var $btn = $('#submit-btn');
        $btn.prop('disabled', true);
        $('#submit-text').text('Updating...');
        $('#submit-spinner').removeClass('d-none');

        $.ajax({
            url: '{{ route("admin.polls.update", $poll) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.polls.show", $poll) }}';
                    }, 500);
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $('#submit-text').text('Update Poll');
                $('#submit-spinner').addClass('d-none');

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(field) {
                        $('[data-field="' + field + '"]').text(errors[field][0]);
                    });
                } else {
                    showToast('Something went wrong. Please try again.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
@endsection
