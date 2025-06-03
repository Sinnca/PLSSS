<?php
// ... existing code ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #23272f 0%, #2d3a5a 100%);
            color: #e0e6f7;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #23272f;
            border-radius: 2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 2rem;
        }
        h1 {
            color: #4f8cff;
            margin-bottom: 2rem;
        }
        .search-bar {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }
        .search-bar input[type='text'] {
            flex: 1;
            padding: 0.7rem 1rem;
            border-radius: 1rem;
            border: none;
            background: #2d3a5a;
            color: #e0e6f7;
            font-size: 1rem;
        }
        .search-bar button {
            padding: 0.7rem 1.5rem;
            border-radius: 1rem;
            border: none;
            background: #4f8cff;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-bar button:hover {
            background: #2563eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #2d3a5a;
            border-radius: 1.2rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
        }
        th, td {
            padding: 1rem;
            text-align: left;
        }
        th {
            background: #23272f;
            color: #4f8cff;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #26304a;
        }
        tr:hover {
            background: #34405a;
        }
        .actions {
            display: flex;
            gap: 1rem;
        }
        .action-btn {
            background: none;
            border: none;
            color: #4f8cff;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .action-btn:hover {
            color: #ff6b6b;
        }
        .badge {
            display: inline-block;
            padding: 0.4em 1em;
            border-radius: 1em;
            font-size: 0.95em;
            font-weight: 600;
        }
        .badge-success { background: #10b981; color: #fff; }
        .badge-warning { background: #f59e0b; color: #fff; }
        .badge-danger { background: #ef4444; color: #fff; }
        .badge-secondary { background: #64748b; color: #fff; }
        .no-appointments {
            color: #8ca3f8;
            text-align: center;
            padding: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-calendar-check"></i> Manage Appointments</h1>
        <form class="search-bar" method="get">
            <input type="text" name="search" placeholder="Search by client, consultant, or purpose...">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Client</th>
                    <th>Consultant</th>
                    <th>Specialty</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="8" class="no-appointments">No appointments found.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($appointment['slot_date'])); ?></td>
                        <td><?php 
                            $start_time = strtotime($appointment['slot_time']);
                            $end_time = strtotime($appointment['slot_time'] . ' +1 hour');
                            echo date('g:i A', $start_time) . ' - ' . date('g:i A', $end_time);
                        ?></td>
                        <td><?php echo htmlspecialchars($appointment['client_name']); ?><br><small><?php echo htmlspecialchars($appointment['client_email']); ?></small></td>
                        <td><?php echo htmlspecialchars($appointment['consultant_name']); ?><br><small><?php echo htmlspecialchars($appointment['consultant_email']); ?></small></td>
                        <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                        <td>
                            <?php 
                            $status_class = 'badge-secondary';
                            switch (strtolower($appointment['status'])) {
                                case 'approved': $status_class = 'badge-success'; break;
                                case 'pending': $status_class = 'badge-warning'; break;
                                case 'cancelled': 
                                case 'rejected': $status_class = 'badge-danger'; break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                        </td>
                        <td class="actions">
                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="action-btn" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                            <a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" class="action-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this appointment?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 