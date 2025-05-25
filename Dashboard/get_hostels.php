<?php
include('connection.php');

$campus_id = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : 0;
$dropdown = isset($_GET['dropdown']) && $_GET['dropdown'] === 'true';

if ($dropdown) {
    // Return options for dropdown
    $query = mysqli_query($connection, "SELECT id, name FROM hostels WHERE campus_id = $campus_id ORDER BY name");
    while ($hostel = mysqli_fetch_assoc($query)) {
        echo "<option value='{$hostel['id']}'>" . htmlspecialchars($hostel['name']) . "</option>";
    }
} else {
    // Return table rows
    $query = mysqli_query($connection, "SELECT * FROM hostels WHERE campus_id = $campus_id ORDER BY name");
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($hostel = mysqli_fetch_assoc($query)): ?>
            <tr>
                <td><?php echo $hostel['id']; ?></td>
                <td><?php echo htmlspecialchars($hostel['name']); ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editHostel(<?php echo $hostel['id']; ?>, '<?php echo htmlspecialchars($hostel['name']); ?>', <?php echo $hostel['campus_id']; ?>)">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteHostel(<?php echo $hostel['id']; ?>)">Delete</button>
                    <button class="btn btn-sm btn-info" onclick="showRooms(<?php echo $hostel['id']; ?>)">View Rooms</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
}
?> 