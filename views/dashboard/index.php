<?php
	$sort_by = $_GET['sort_by'] ?? 'id';
	$sort_order = $_GET['sort_order'] ?? 'desc';
	$search = $_GET['search'] ?? '';
?>
<style>
#notif-box {
    width: 250px;
    border: 1px solid #ccc;
    padding: 10px;
    display: none;
    background: white;
    position: absolute;
    top: 40px;
}
.notif-item {
    padding: 5px;
    border-bottom: 1px solid #eee;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" style="margin-bottom: 0;">
                <li><a href="index.php?page=addons&action=list"><h4>Dashboard</h4></a></li>
            </ol>
        </nav>
    </div>

    <!-- <div class="more-link" style="display: flex; align-items: center; gap: 8px;">
        <form action="index.php" method="get" class="d-flex">
            <div class="input-group">
                <input type="hidden" name="page" value="addons" />
                <input type="hidden" name="action" value="list" />
                <input class="form-control" type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search">
                <button class="btn btn-primary px-3" type="submit">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </form>
        <a href="index.php?page=products&action=addEditAddon" class="btn btn-primary text-white" style="text-decoration:none;" >
            <i class="fa fa-plus me-1"></i> Add
        </a>
    </div> -->
</div>
<button id="notif-btn">
    Notifications <span id="notif-count"></span>
</button>

<div id="notif-box"></div>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-0 p-0 ">
  <div class="container-fluid">
    <div class="collapse navbar-collapse">
        <div class="container">
            <div class="row">
                <div class="col ">
                <ul class="navbar-nav">
                    <li class="nav-item">
                    <a class="nav-link navbar-active-bottom" aria-current="page" href="#">New 0</a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" href="#">Completed</a>
                    </li>
                </ul>
                </div>
                <div class="col">
                    <div class="dropdown ms-auto btn border-light-grey" >
                        <span class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            50 Orders per page
                        </span>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">10 Orders per page</a></li>
                            <li><a class="dropdown-item" href="#">20 Orders per page</a></li>
                            <li><a class="dropdown-item" href="#">50 Orders per page</a></li>
                            <li><a class="dropdown-item" href="#">100 Orders per page</a></li>
                        </ul>
                    </div>
                </div>
               
            </div>
        </div>
        
    </div>
  </div>
</nav>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-0 p-0">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Navbar</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item ">
          <a class="nav-link navbar-active-bottom" aria-current="page" href="#">All Orders</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">No. PO</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">PO Sent</a>
        </li>
        <li class="nav-item">
          <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div class="container my-4">
  <h5 class="mb-4">Manage Orders</h5>
  <div class="row g-3">
    
    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-list-check"></i></div>
        <h6 class="mb-1 ">PO Pending</h6>
        <h3 class="mb-1">56</h3>
        <div class="percent-up"><i class="fa-solid fa-arrow-up"></i> 16% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card ">
        <div class="icon-circle float-start"><i class="fa-solid fa-file-signature"></i></div>
        <h6 class="mb-1">PO Sent</h6>
        <h3 class="mb-1">26</h3>
        <div class="percent-down"><i class="fa-solid fa-arrow-down"></i> 1% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-calendar-days"></i></div>
        <h6 class="mb-1">Due Receipt</h6>
        <h3 class="mb-1">16</h3>
        <div class="percent-down"><i class="fa-solid fa-arrow-down"></i> 6% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-hand-holding"></i></div>
        <h6 class="mb-1">Received</h6>
        <h3 class="mb-1">42</h3>
        <div class="percent-up"><i class="fa-solid fa-arrow-up"></i> 16% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-truck"></i></div>
        <h6 class="mb-1">Shipped</h6>
        <h3 class="mb-1">25</h3>
        <div class="percent-down"><i class="fa-solid fa-arrow-down"></i> 6% this month</div>
      </div>
    </div>

  </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th><?= sort_link('id', 'ID', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th><?= sort_link('addon_name', 'Addon Name', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th><?= sort_link('description ', 'Description', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th><?= sort_link('active', 'Active', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
			/*$addons = $data['addons']['addons'];
			
			if (!empty($addons)){
				$i=0;
				foreach ($addons as $addon){
				//print_r($userdata);
			?>
				
                <tr data-id="<?= $addon['id'] ?>" data-addon_name="<?= htmlspecialchars($addon['addon_name']) ?>" data-description="<?= htmlspecialchars($addon['description']) ?>" " data-active="<?= htmlspecialchars($addon['active']) ?>">
                    <td><?= $addon['id'] ?></td>
                    <td><?= htmlspecialchars($addon['addon_name']) ?></td>
                    <td><?= htmlspecialchars($addon['description']) ?></td>
                    <td><?= $addon['active'] == 1 ? 'Yes' : 'No' ?></td>
                    <td>
                        <a href="index.php?page=products&action=addEditAddon&id=<?= $addon['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
						<button class="btn btn-sm btn-danger" onclick="deleteData(<?= $addon['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
				<?php 
						$i++;
					} ?>
			<?php }else{ ?>
            <tr><td colspan="8" class="text-center">No addon found.</td></tr>
			<?php } */?>
        </tbody>
    </table>
	<?php if ($data['total_pages'] > 1): ?>
		<nav aria-label="Page navigation" class="mt-3">
		<ul class="pagination justify-content-center">
		<!-- Previous Button -->
		  <li class="page-item <?= $page_no <= 1 ? 'disabled' : '' ?>">
			<a class="page-link" href="?page=products&action=addEditAddon&page_no=<?= $page_no - 1 ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" aria-label="Previous">
			  <span aria-hidden="true">&laquo;</span>
			</a>
		  </li>
		<?php for ($i = 1; $i <= $total_pages; $i++): ?>
			<li class="page-item <?= $i == $page_no ? 'active' : '' ?>">
			<a class="page-link" href="index.php?page=products&action=addEditAddon&page_no=<?= $i ?>" 
			   class="<?= ($page_no == $i) ? 'active' : '' ?>">
			   <?= $i ?>
			</a>
			</li>
		<?php endfor; ?>
			 <!-- Next Button -->
		  <li class="page-item <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
			<a class="page-link" href="?page=products&action=addEditAddon&page_no=<?= $page_no + 1 ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" aria-label="Next">
			  <span aria-hidden="true">&raquo;</span>
			</a>
		  </li>
			</ul>
		</nav>
	<?php endif; ?>
</div>
<script>
  function deleteData(dataId) {
		if (!confirm("Are you sure you want to delete this addon?")) return;
		try {
			fetch('index.php?page=products&action=delete_addons', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: `id=${encodeURIComponent(dataId)}`
			})
			.then(response => response.json())
			.then(result => {
				if (result.success) {
					// Remove row from DOM
					document.querySelector(`tr[data-id='${dataId}']`).remove();
					alert(result.message);
				} else {
					alert(result.message || "Failed to delete.");
				}
			})
		} catch (error) {
			//console.error('Fetch error:', error);
			alert('An error occurred while submitting the form. Check console for details.');
		}
	}
  /*setInterval(() => {
    fetch("index.php?page=notifications&action=fetch_notifications")
      .then(response => response.json())
      .then(data => {
          data.forEach(n => {
              showNotification(n.message);
          });
      });
  }, 5000);

  function showNotification(message){
      alert("🔔 " + message);
  }*/
  function loadNotifications() {
    $.ajax({
        url: "index.php?page=notifications&action=fetch_notifications",
        method: "GET",
        success: function(data) {
            console.log(data);
            let notifs = JSON.parse(data);
            let count = notifs.length;

            $("#notif-count").text(count > 0 ? "(" + count + ")" : "");

            let html = "";
            let ids = [];

            notifs.forEach(n => {
                html += `<div class='notif-item'>${n.message}</div>`;
                ids.push(n.id);
            });

            $("#notif-box").html(html);

            // Auto mark as read when dropdown opens
            $("#notif-btn").off().on("click", function() {
                $("#notif-box").toggle();

                if (ids.length > 0) {
                    $.post("index.php?page=notifications&action=mark_as_read", { ids: ids });
                }
            });
        },
        error: function() {
            console.error("Failed to fetch notifications.");
        }
    });
}

// Load notifications every 5 seconds
/*setInterval(loadNotifications, 5000);
loadNotifications();*/

document.addEventListener("DOMContentLoaded", () => {
    if (Notification.permission !== "granted") {
        Notification.requestPermission();
    }
});

function checkNewNotification() {
    $.get("index.php?page=notifications&action=fetch_notifications", function(data) {
        let notif = JSON.parse(data);

        let html = "";
        let ids = [];

        notifs.forEach(n => {
            html += `<div class='notif-item'>${n.message}</div>`;
            ids.push(n.id);
        });

        if (notif && notif.message) {
            showBrowserNotification(notif.message, notif.id);
        }
    });
}

function showBrowserNotification(message, id) {
    if (Notification.permission === "granted") {
        let notification = new Notification("New Notification", {
            body: message,
            icon: "bell.png" // optional icon
        });

        // Mark as read after showing
        $.post("index.php?page=notifications&action=mark_as_read", { ids: [id] });
    }
}

// Check every 5 seconds
//setInterval(checkNewNotification, 5000);
</script>