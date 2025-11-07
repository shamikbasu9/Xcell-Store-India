<?php
$pageTitle = 'Categories Management';
require_once 'includes/header.php';

$conn = getDBConnection();
$error = '';
$success = '';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $icon = sanitize($_POST['icon']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        $slug = generateSlug($name);
        
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $slug, $description, $icon, $id);
            $success = 'Category updated successfully';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $slug, $description, $icon);
            $success = 'Category added successfully';
        }
        
        $stmt->execute();
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM categories WHERE id = $id");
    $success = 'Category deleted successfully';
}

// Get categories
$categories = [];
$result = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                        FROM categories c 
                        LEFT JOIN products p ON c.id = p.category_id 
                        GROUP BY c.id ORDER BY c.name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editCategory = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="fw-bold mb-0">
                    <?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Icon (FontAwesome class)</label>
                        <input type="text" class="form-control" name="icon" placeholder="fa-leaf"
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['icon']) : ''; ?>">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $editCategory ? 'Update' : 'Add'; ?>
                        </button>
                        <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No categories yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <i class="fas <?php echo $category['icon']; ?> text-success me-2"></i>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $category['product_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-outline-danger"
                                                   onclick="return confirm('Delete this category?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
