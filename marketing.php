<script>
    // Campaign Management
    document.querySelectorAll('.edit-campaign').forEach(button => {
        button.addEventListener('click', function() {
            const campaignId = this.dataset.id;
            fetch(`get_campaign.php?id=${campaignId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the edit form with campaign data
                    document.getElementById('edit_campaign_id').value = data.id;
                    document.getElementById('edit_campaign_name').value = data.name;
                    document.getElementById('edit_campaign_description').value = data.description;
                    document.getElementById('edit_campaign_type').value = data.campaign_type;
                    document.getElementById('edit_campaign_start_date').value = data.start_date;
                    document.getElementById('edit_campaign_end_date').value = data.end_date;
                    document.getElementById('edit_campaign_target_audience').value = data.target_audience;
                    document.getElementById('edit_campaign_content').value = data.content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching campaign details');
                });
        });
    });

    document.querySelectorAll('.delete-campaign').forEach(button => {
        button.addEventListener('click', function() {
            if(confirm('Are you sure you want to delete this campaign?')){
                const campaignId = this.dataset.id;
                fetch('delete_campaign.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: campaignId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success){
                        location.reload();
                    } else {
                        alert(data.error || 'Error deleting campaign');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting campaign');
                });
            }
        });
    });

    // Notification Management
    document.querySelectorAll('.edit-notification').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            fetch(`get_notification.php?id=${notificationId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the edit form with notification data
                    document.getElementById('edit_notification_id').value = data.id;
                    document.getElementById('edit_notification_title').value = data.title;
                    document.getElementById('edit_notification_message').value = data.message;
                    document.getElementById('edit_notification_type').value = data.notification_type;
                    document.getElementById('edit_notification_target_audience').value = data.target_audience;
                    document.getElementById('edit_notification_scheduled_at').value = data.scheduled_at;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching notification details');
                });
        });
    });

    document.querySelectorAll('.delete-notification').forEach(button => {
        button.addEventListener('click', function() {
            if(confirm('Are you sure you want to delete this notification?')){
                const notificationId = this.dataset.id;
                fetch('delete_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: notificationId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success){
                        location.reload();
                    } else {
                        alert(data.error || 'Error deleting notification');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting notification');
                });
            }
        });
    });

    // Form Submissions
    document.getElementById('edit_campaign_form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = {
            id: document.getElementById('edit_campaign_id').value,
            name: document.getElementById('edit_campaign_name').value,
            description: document.getElementById('edit_campaign_description').value,
            campaign_type: document.getElementById('edit_campaign_type').value,
            start_date: document.getElementById('edit_campaign_start_date').value,
            end_date: document.getElementById('edit_campaign_end_date').value,
            target_audience: document.getElementById('edit_campaign_target_audience').value,
            content: document.getElementById('edit_campaign_content').value
        };

        fetch('update_campaign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                location.reload();
            } else {
                alert(data.error || 'Error updating campaign');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating campaign');
        });
    });

    document.getElementById('edit_notification_form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = {
            id: document.getElementById('edit_notification_id').value,
            title: document.getElementById('edit_notification_title').value,
            message: document.getElementById('edit_notification_message').value,
            notification_type: document.getElementById('edit_notification_type').value,
            target_audience: document.getElementById('edit_notification_target_audience').value,
            scheduled_at: document.getElementById('edit_notification_scheduled_at').value
        };

        fetch('update_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                location.reload();
            } else {
                alert(data.error || 'Error updating notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating notification');
        });
    });
</script> 