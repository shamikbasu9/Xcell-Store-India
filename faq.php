<?php
$pageTitle = 'FAQ';
require_once 'includes/header.php';

$conn = getDBConnection();
$faqs = [];
$result = $conn->query("SELECT * FROM faqs WHERE is_active = TRUE ORDER BY display_order");
while ($row = $result->fetch_assoc()) {
    $faqs[] = $row;
}
$conn->close();
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold">Frequently Asked Questions</h1>
        <p class="text-muted">Find answers to common questions</p>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                    type="button" data-mdb-toggle="collapse" 
                                    data-mdb-target="#faq<?php echo $faq['id']; ?>">
                                <?php echo htmlspecialchars($faq['question']); ?>
                            </button>
                        </h2>
                        <div id="faq<?php echo $faq['id']; ?>" 
                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                             data-mdb-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-2">Still have questions?</h5>
                    <p class="text-muted mb-3">Contact our support team</p>
                    <a href="contact.php" class="btn btn-success">
                        <i class="fas fa-envelope"></i> Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize accordion functionality
document.addEventListener('DOMContentLoaded', function() {
    const accordionButtons = document.querySelectorAll('.accordion-button');
    
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-mdb-target');
            const targetCollapse = document.querySelector(targetId);
            const isExpanded = !this.classList.contains('collapsed');
            
            // Close all accordion items
            document.querySelectorAll('.accordion-collapse').forEach(collapse => {
                if (collapse !== targetCollapse) {
                    collapse.classList.remove('show');
                    const btn = document.querySelector(`[data-mdb-target="#${collapse.id}"]`);
                    if (btn) btn.classList.add('collapsed');
                }
            });
            
            // Toggle current item
            if (isExpanded) {
                targetCollapse.classList.remove('show');
                this.classList.add('collapsed');
            } else {
                targetCollapse.classList.add('show');
                this.classList.remove('collapsed');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
