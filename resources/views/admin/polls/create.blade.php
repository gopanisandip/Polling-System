@extends('layouts.app')

@section('title', 'Create Poll - Admin Dashboard')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-3">
            <a href="{{ route('admin.polls.index') }}" class="text-primary small">
                <i class="fas fa-arrow-left mr-1"></i> Back to My Polls
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 font-weight-bold mb-4">Create New Poll</h1>

                <form id="create-poll-form">
                    @csrf
                    <div class="form-group">
                        <label for="title">Question<span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" required class="form-control"
                            placeholder="Enter you poll question...">
                        <div class="text-danger small mt-1 error-msg" data-field="title"></div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description <span class="text-muted font-weight-normal">(optional)</span></label>
                        <textarea name="description" id="description" rows="3" class="form-control"
                            placeholder="Enter questiondescription..."></textarea>
                        <div class="text-danger small mt-1 error-msg" data-field="description"></div>
                    </div>

                    <div class="form-group">
                        <label>Poll Options <span class="text-danger">*</span> <span class="text-muted font-weight-normal">(minimum 2)</span></label>

                        <div id="options-container">
                            <div class="input-group mb-2 option-row">
                                <div class="input-group-prepend">
                                    <span class="input-group-text option-number">1</span>
                                </div>
                                <input type="text" name="options[]" required class="form-control option-input" placeholder="Option 1">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary remove-option" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="input-group mb-2 option-row">
                                <div class="input-group-prepend">
                                    <span class="input-group-text option-number">2</span>
                                </div>
                                <input type="text" name="options[]" required class="form-control option-input" placeholder="Option 2">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary remove-option" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-danger small mt-1 error-msg" data-field="options"></div>

                        <button type="button" id="add-option-btn" class="btn btn-link text-primary p-0 mt-1 small">
                            <i class="fas fa-plus mr-1"></i> Add Another Option
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="end_at">End Date <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input type="datetime-local" name="end_at" id="end_at" class="form-control">
                        <small class="form-text text-muted">Leave empty for no expiration</small>
                        <div class="text-danger small mt-1 error-msg" data-field="end_at"></div>
                    </div>

                    <hr>
                    <button type="submit" id="submit-btn" class="btn btn-primary btn-block btn-lg">
                        <span id="submit-text">Create Poll</span>
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
    var maxOptions = 20;

    $('#add-option-btn').on('click', function() {
        var count = $('.option-row').length;
        if (count >= maxOptions) {
            showToast('Maximum ' + maxOptions + ' options allowed', 'info');
            return;
        }

        var num = count + 1;
        var html = '<div class="input-group mb-2 option-row">' +
            '<div class="input-group-prepend"><span class="input-group-text option-number">' + num + '</span></div>' +
            '<input type="text" name="options[]" required class="form-control option-input" placeholder="Option ' + num + '">' +
            '<div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-option"><i class="fas fa-times"></i></button></div>' +
            '</div>';

        $('#options-container').append(html);
        $('#options-container .option-row:last .option-input').focus();
        updateRemoveButtons();
    });

    $(document).on('click', '.remove-option:not([disabled])', function() {
        $(this).closest('.option-row').fadeOut(200, function() {
            $(this).remove();
            renumberOptions();
            updateRemoveButtons();
        });
    });

    function renumberOptions() {
        $('.option-row').each(function(i) {
            $(this).find('.option-number').text(i + 1);
            $(this).find('.option-input').attr('placeholder', 'Option ' + (i + 1));
        });
    }

    function updateRemoveButtons() {
        var count = $('.option-row').length;
        $('.remove-option').each(function() {
            if (count <= 2) {
                $(this).prop('disabled', true).removeClass('btn-outline-danger').addClass('btn-outline-secondary');
            } else {
                $(this).prop('disabled', false).removeClass('btn-outline-secondary').addClass('btn-outline-danger');
            }
        });
    }

    $('#create-poll-form').on('submit', function(e) {
        e.preventDefault();

        $('.error-msg').text('');
        $('.is-invalid').removeClass('is-invalid');

        var $btn = $('#submit-btn');
        $btn.prop('disabled', true);
        $('#submit-text').text('Creating...');
        $('#submit-spinner').removeClass('d-none');

        $.ajax({
            url: '{{ route("admin.polls.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 500);
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $('#submit-text').text('Create Poll');
                $('#submit-spinner').addClass('d-none');

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(field) {
                        var cleanField = field.replace(/\.\d+$/, '');
                        var $errorDiv = $('[data-field="' + cleanField + '"]');
                        $errorDiv.text(errors[field][0]);

                        var $input = $('[name="' + field + '"]');
                        if ($input.length) $input.addClass('is-invalid');
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
