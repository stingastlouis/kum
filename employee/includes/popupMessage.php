<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Success</h5>
            </div>
            <div class="modal-body" id="successMessage"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Error</h5>
            </div>
            <div class="modal-body" id="errorMessage"></div>
        </div>
    </div>
</div>

<script>
    function showModalFromQuery() {
        const params = new URLSearchParams(window.location.search);
        const success = params.get('success');
        const error = params.get('error');

        if (!success && !error) {
            return;
        }

        if (success) {
            document.getElementById('successMessage').textContent = success;
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();

            setTimeout(() => {
                successModal.hide();
                clearQueryParams();
            }, 3000);
        }

        if (error) {
            document.getElementById('errorMessage').textContent = error;
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();

            setTimeout(() => {
                errorModal.hide();
                clearQueryParams();
            }, 3000);
        }
    }

    function clearQueryParams() {
        const url = new URL(window.location);
        url.search = '';
        window.history.replaceState({}, document.title, url);
    }

    document.addEventListener('DOMContentLoaded', showModalFromQuery);
</script>