document.addEventListener('DOMContentLoaded', function () {
    const fromDateInput = document.getElementById('from_date');
    const toDateInput = document.getElementById('to_date');
    const updateDateRangeButton = document.getElementById('update-date-range');
    const tableContainer = document.getElementById('tickets-table-container');
    const statusSummaryContainer = document.querySelector('.card-container .card:nth-child(1) .card-body');
    const billingSummaryContainer = document.querySelector('.card-container .card:nth-child(2) .card-body');

    let activeStatusFilter = null; // Store the currently selected status filter



    // Fetch and update data
    function fetchAndUpdateData(fromDate, toDate, statusFilter = null) {
        const payload = {
            from_date: fromDate,
            to_date: toDate,
            status_filter: statusFilter,
        };

        fetch('filter_tickets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Update date range inputs
                fromDateInput.value = data.from_date;
                toDateInput.value = data.to_date;

                // Update the status summary card
                let statusHtml = '<div class="btn-group-vertical w-100" role="group" aria-label="Ticket Status Summary">';
                data.statusCounts.forEach((status) => {
                    statusHtml += `
                        <input 
                            type="radio" 
                            class="btn-check" 
                            name="status_filter" 
                            id="status_${status.id}" 
                            value="${status.id}" 
                            ${activeStatusFilter == status.id ? 'checked' : ''}
                        >
                        <label 
                            class="btn btn-outline-primary d-flex justify-content-between align-items-center w-100 mb-2" 
                            for="status_${status.id}">
                            <span class="text-start">${status.status_name || 'Unknown Status'}</span>
                            <span class="text-end badge bg-primary">${status.count || 0}</span>
                        </label>
                    `;
                });
                statusHtml += '</div>';
                statusSummaryContainer.innerHTML = statusHtml;

                // Update the billing summary card
                let billingHtml = '<ul class="list-group list-group-flush">';
                data.billingCounts.forEach((billing) => {
                    billingHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ${billing.billing_status || 'Unknown Status'}
                            <span class="badge bg-success">${billing.count || 0}</span>
                        </li>
                    `;
                });
                billingHtml += '</ul>';
                billingSummaryContainer.innerHTML = billingHtml;

                // Update the tickets table
                const tickets = data.tickets;
                let tableHtml = `
                    <table class="table">
                     <thead>
                        <tr>
                            <th hidden ><input type="checkbox" id="select-all"></th>
                            <th>Ticket ID</th>
                            <th>Ticket Date</th> <!-- New column for Ticket Date -->
                            <th>Account Name</th>
                            <th>Ticket Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                `;

                if (tickets.length > 0) {
                    tickets.forEach((ticket) => {
                        const ticketDate = new Date(ticket['Ticket Date']);
                        const formattedDate = ticketDate.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                        });
                
                        tableHtml += `
                            <tr>
                                 <td>
                                    <a href="ticket_details.php?ticket_id=${ticket['Ticket ID']}&token=${ticket['Ticket Token']}">
                                    ${ticket['Internal Ticket ID']}</a>
                                </td>
                                <td>${formattedDate}</td>
                                <td>${ticket['Account Name']}</td>
                                <td>${ticket['Ticket Type']}</td>
                                <td>${ticket['Status']}</td>
                                
                            </tr>
                        `;
                    });
                } else {
                    tableHtml += `
                        <tr>
                            <td colspan="8">No tickets found.</td>
                        </tr>
                    `;
                }
                

                tableHtml += '</tbody></table>';
                tableContainer.innerHTML = tableHtml;

                // Reattach "Select All" functionality
                document.getElementById('select-all').addEventListener('click', function () {
                    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="ticket_ids[]"]');
                    checkboxes.forEach((checkbox) => (checkbox.checked = this.checked));
                });

                // Reattach radio button event listeners
                attachRadioListeners();
                attachRowListeners(); // Reattach row listeners
            })
            .catch((error) => {
                console.error('Error fetching data:', error);
            });
    }

    // Attach radio button listeners
    function attachRadioListeners() {
        document.querySelectorAll('input[name="status_filter"]').forEach((radio) => {
            radio.addEventListener('click', function () {
                activeStatusFilter = this.value;
                fetchAndUpdateData(fromDateInput.value, toDateInput.value, activeStatusFilter);
            });
        });
    }

    // Handle date range update
    updateDateRangeButton.addEventListener('click', function () {
        const fromDate = fromDateInput.value;
        const toDate = toDateInput.value;

        // Validate date range
        if (new Date(fromDate) > new Date(toDate)) {
            alert('The "From" date cannot be later than the "To" date.');
            return;
        }

        activeStatusFilter = null; // Reset status filter when date is updated
        fetchAndUpdateData(fromDate, toDate);
    });

    // Initial load
    fetchAndUpdateData(fromDateInput.value, toDateInput.value);

    
    
    
    
});
