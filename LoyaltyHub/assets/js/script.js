
document.addEventListener("DOMContentLoaded", function () {

    // Automatically close alerts
    const alerts = document.querySelectorAll(".alert");

    alerts.forEach(function (alert) {
        setTimeout(function () {
            const instance = bootstrap.Alert.getOrCreateInstance(alert);
            instance.close();
        }, 4000);
    });

    // Confirm delete actions
    document.querySelectorAll("[data-confirm]").forEach(function (button) {
        button.addEventListener("click", function (event) {
            if (!confirm(this.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

});
