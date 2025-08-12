async function deleteData(dataId) {
    if (!confirm("Are you sure you want to delete this user?")) return;

    const response = await fetch('controllers/UserController.php?action=delete', {
        method: 'POST',
        body: new URLSearchParams({ id: dataId })
    });

    const result = await response.json();
    if (result.success) {
        document.querySelector(`tr[data-id='${dataId}']`).remove();
        alert("User deleted successfully.");
    } else {
        alert("Failed to delete user.");
    }
}
