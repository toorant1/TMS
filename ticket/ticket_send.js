function sendWhatsAppMessage(target, ticketId) {
    // Define the API configuration
    const apiEndpoint = "https://publicapi.myoperator.co/chat/messages"; // Replace with the actual WhatsApp API endpoint
    const bearerToken = "tqYr8PooE3HGtJ1LGR3fMP4sQUtcpd1rZARFUuwiSc"; // Replace with your actual Bearer token
    const phoneNumberId = "553313077858045"; // Replace with your phone number ID
    const companyId = "676fbe2232cb3202"; // Replace with your company ID
    const templateName = "new_ticket_registration"; // Replace with your WhatsApp template name

    // Fetch ticket details dynamically from the server
    fetch('get_ticket_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ticket_id=${encodeURIComponent(ticketId)}`,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.status === 'success') {
                const ticket = data.data;
                let phoneNumber = '';
                let templateData = {};

                // Determine recipient type and construct the WhatsApp payload
                if (target === 'client') {
                    phoneNumber = ticket.contact_phone1; // Use client's phone number
                    templateData = {
                        "1": ticket.customer_name, // Customer Name
                        "2": ticket.address, // Customer Address
                        "3": ticket.contact_name, // Contact Name
                        "4": `${ticket.contact_phone1} - ${ticket.contact_phone2}`, // Contact Phones
                        "5": ticket.ticket_id, // Ticket ID
                        "6": ticket.ticket_date, // Ticket Date
                        "7": ticket.ticket_status, // Ticket Status
                        "8": ticket.ticket_priority, // Ticket Priority
                        "9": ticket.main_cause, // Main Cause
                        "10": ticket.problem_statement, // Problem Statement
                        "11": ticket.account_email_id, // Account Email
                    };
                } else if (target === 'engineer') {
                    phoneNumber = ticket.contact_phone2; // Use engineer's phone number
                    templateData = {
                        "1": ticket.customer_name,
                        "2": ticket.address,
                        "3": ticket.contact_name,
                        "4": `${ticket.contact_phone1} - ${ticket.contact_phone2}`,
                        "5": ticket.ticket_id,
                        "6": ticket.ticket_date,
                        "7": ticket.ticket_status,
                        "8": ticket.ticket_priority,
                        "9": ticket.main_cause,
                        "10": ticket.problem_statement,
                        "11": ticket.account_email_id,
                    };
                }

                // Construct the API payload
                const payload = {
                    phone_number_id: phoneNumberId,
                    customer_country_code: "91",
                    customer_number: phoneNumber,
                    data: {
                        type: "template",
                        context: {
                            template_name: templateName,
                            language: "en",
                            body: templateData,
                        },
                    },
                    reply_to: null,
                    myop_ref_id: `ref_${ticketId}_${target}_${Date.now()}`,
                };

                // Make the API call to send the WhatsApp message
                fetch(apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${bearerToken}`,
                        'X-MYOP-COMPANY-ID': companyId,
                    },
                    body: JSON.stringify(payload),
                })
                    .then((apiResponse) => apiResponse.json())
                    .then((apiData) => {
                        if (apiData.status === 'success') {
                            alert(`Message sent successfully to ${target}!`);
                        } else {
                            console.error('WhatsApp API Error:', apiData);
                            alert('Failed to send WhatsApp message. Please try again later.');
                        }
                    })
                    .catch((apiError) => {
                        console.error('Error sending WhatsApp message:', apiError);
                        alert('An unexpected error occurred while sending the WhatsApp message.');
                    });
            } else {
                alert('Error fetching ticket details: ' + data.message);
            }
        })
        .catch((error) => {
            console.error('Error fetching ticket details:', error);
            alert('An error occurred while fetching ticket details.');
        });
}
