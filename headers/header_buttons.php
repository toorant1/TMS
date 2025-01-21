<!-- Buttons Section -->
<div class="d-flex justify-content-left mt-5">
    <div class="row text-center">
        <style>
            .custom-btn {
                font-size: 1rem;
                padding: 15px;
                border-radius: 10px;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .custom-btn:hover {
                transform: scale(1.05);
                box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.15);
            }

            .custom-btn-primary {
                background: linear-gradient(45deg, #1e90ff, #87cefa);
                color: white;
            }

            .custom-btn-secondary {
                background: linear-gradient(45deg, #6c757d, #a8a8a8);
                color: white;
            }

            .custom-btn-success {
                background: linear-gradient(45deg, #28a745, #7dcea0);
                color: white;
            }

            .custom-btn-danger {
                background: linear-gradient(45deg, #dc3545, #f1948a);
                color: white;
            }

            .custom-btn-warning {
                background: linear-gradient(45deg, #ffc107, #ffea8a);
                color: white;
            }

            .custom-btn i {
                margin-right: 8px;
            }
        </style>

<style>
    @media (max-width: 768px) {
        .custom-btn {
            font-size: 0.9rem;
            padding: 10px;
        }

        .col-md-2 {
            flex: 0 0 100%; /* Make each button take the full width */
            max-width: 100%;
        }

        .mb-3 {
            margin-bottom: 1rem !important; /* Ensure consistent spacing */
        }
    }
</style>


        <!-- Ensure Font Awesome is loaded -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

        <div class="col-md-2 mb-3">
            <button type="button" class="btn custom-btn custom-btn-primary w-100"
                onclick="window.location.href='<?= base_url('ticket/dashboard.php'); ?>'">
                <i class="fas fa-ticket-alt"></i> Ticket
            </button>
        </div>
        <div class="col-md-2 mb-3">
            <button type="button" class="btn custom-btn custom-btn-secondary w-100"
                onclick="window.location.href='<?= base_url('accounts/dashboard.php'); ?>'">
                <i class="fas fa-user-circle"></i> Account
            </button>
        </div>
        <div class="col-md-2 mb-3">
            <button type="button" class="btn custom-btn custom-btn-success w-100"
                onclick="window.location.href='<?= base_url('marketing/dashboard.php'); ?>'">
                <i class="fas fa-bullhorn"></i> Marketing
            </button>
        </div>
        <div class="col-md-2 mb-3">
            <button type="button" class="btn custom-btn custom-btn-danger w-100"
                onclick="window.location.href='<?= base_url('billing/dashboard.php'); ?>'">
                <i class="fas fa-file-invoice-dollar"></i> Billing
            </button>
        </div>
        <div class="col-md-2 mb-3">
            <button type="button" class="btn custom-btn custom-btn-warning w-100"
                onclick="window.location.href='<?= base_url('materials/dashboard.php'); ?>'">
                <i class="fas fa-cubes"></i> Materials
            </button>
        </div>
    </div>
</div>
