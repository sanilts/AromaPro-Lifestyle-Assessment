<footer class="bg-dark text-white mt-5 p-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?php echo APP_NAME; ?></h5>
                <p class="text-muted">Email list validation with Postmark integration.</p>
            </div>
            <div class="col-md-3">
                <h5>Links</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>/dashboard.php" class="text-light">Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/lists/index.php" class="text-light">My Lists</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/profile.php" class="text-light">My Profile</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Resources</h5>
                <ul class="list-unstyled">
                    <li><a href="https://postmarkapp.com/support" target="_blank" class="text-light">Postmark Support</a></li>
                    <li><a href="https://postmarkapp.com/developer" target="_blank" class="text-light">Postmark API</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/help.php" class="text-light">Help Center</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            <p class="text-muted small">Version <?php echo APP_VERSION; ?></p>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>