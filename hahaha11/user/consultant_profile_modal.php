<?php
require_once '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<div class="alert alert-danger">Invalid consultant ID.</div>';
    exit;
}

$sql = "SELECT c.*, u.name, u.email FROM consultants c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = $id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">Consultant not found.</div>';
    exit;
}

$consultant = mysqli_fetch_assoc($result);
?>
<style>
.consultant-modal-content {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 8px 32px rgba(37,99,235,0.12);
    padding: 2.5rem 2rem 2rem 2rem;
    max-width: 700px;
    margin: 0 auto;
    color: #1e293b;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    border: 4px solid #2563eb;
}
.consultant-modal-left {
    text-align: center;
    border-right: 2px solid #e0e7ff;
    padding-right: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.consultant-modal-img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #2563eb;
    box-shadow: 0 4px 16px rgba(37,99,235,0.15);
    margin-bottom: 1rem;
}
.consultant-modal-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 0.2rem;
}
.consultant-modal-specialty {
    color: #3b82f6;
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}
.consultant-modal-right {
    padding-left: 2rem;
}
.consultant-modal-info strong {
    color: #2563eb;
    font-weight: 600;
}
.consultant-modal-info p {
    margin-bottom: 0.5rem;
    font-size: 1.05rem;
}
@media (max-width: 700px) {
    .consultant-modal-content {
        padding: 1.2rem 0.5rem;
    }
    .consultant-modal-left {
        border-right: none;
        border-bottom: 2px solid #e0e7ff;
        padding-right: 0;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .consultant-modal-right {
        padding-left: 0;
    }
    .consultant-modal-row {
        flex-direction: column;
    }
}
.consultant-modal-row {
    display: flex;
    gap: 0;
}
</style>
<div class="consultant-modal-content">
    <div class="consultant-modal-row">
        <div class="col-md-4 consultant-modal-left">
            <?php if(!empty($consultant['profile_photo'])): ?>
                <img src="../<?php echo htmlspecialchars($consultant['profile_photo']); ?>" alt="<?php echo htmlspecialchars($consultant['name']); ?>" class="consultant-modal-img">
            <?php else: ?>
                <div class="no-image consultant-modal-img" style="background:#e0e7ff; display:flex; align-items:center; justify-content:center; font-size:3rem; color:#2563eb;">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
            <div class="consultant-modal-name"><?php echo htmlspecialchars($consultant['name']); ?></div>
            <div class="consultant-modal-specialty"><?php echo htmlspecialchars($consultant['specialty']); ?></div>
        </div>
        <div class="col-md-8 consultant-modal-right">
            <div class="consultant-modal-info">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($consultant['email'] ?? 'Not specified'); ?></p>
                <p><strong>Experience:</strong> <?php echo htmlspecialchars($consultant['experience'] ?? 'Not specified'); ?> years</p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($consultant['location'] ?? 'Not specified'); ?></p>
                <p><strong>Qualification:</strong> <?php echo htmlspecialchars($consultant['qualification'] ?? 'Not specified'); ?></p>
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($consultant['description'] ?? 'Not specified')); ?></p>
            </div>
        </div>
    </div>
</div>