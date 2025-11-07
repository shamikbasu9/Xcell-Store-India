<?php
$pageTitle = 'Support Tickets';
require_once 'includes/header.php';

$conn = getDBConnection();

if (isset($_POST['respond'])) {
    $ticketId = intval($_POST['ticket_id']);
    $response = sanitize($_POST['response']);
    $status = sanitize($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE support_tickets SET admin_response = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $response, $status, $ticketId);
    $stmt->execute();
    $stmt->close();
    
    header('Location: support.php?updated=1');
    exit;
}

$tickets = [];
$result = $conn->query("SELECT * FROM support_tickets ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}

$conn->close();
?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Ticket updated
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No tickets</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['email']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $ticket['status'] === 'closed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-mdb-toggle="modal" 
                                            data-mdb-target="#ticketModal<?php echo $ticket['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <div class="modal fade" id="ticketModal<?php echo $ticket['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                                            <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <strong>Message:</strong>
                                                    <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Response</label>
                                                    <textarea class="form-control" name="response" rows="4"><?php echo htmlspecialchars($ticket['admin_response'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Close</button>
                                                <button type="submit" name="respond" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
