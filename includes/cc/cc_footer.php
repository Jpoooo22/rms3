<?php
/**
 * Closes the shell opened by cc_header.php.
 */
?>
                </div><!-- /.container-fluid -->
            </div><!-- /#content -->

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Command Center &mdash; NRTDC</span>
                    </div>
                </div>
            </footer>
        </div><!-- /#content-wrapper -->
    </div><!-- /#wrapper -->

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <div class="modal fade" id="ccLogoutModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <?php if (!empty($CC_PAGE_SCRIPTS)) { echo $CC_PAGE_SCRIPTS; } ?>

    <?php
    /* Due / overdue reminder, once per session. The Command Center pages use
       this footer rather than cc_nav_buffer.php, so it is raised here too. */
    if (empty($_SESSION['cc_reminder_shown']) && isset($con)) {
        require_once __DIR__ . '/cc_reminders.php';
        $cc_reminder_modal = cc_reminder_html($con, $user['id']);
        if ($cc_reminder_modal !== '') {
            $_SESSION['cc_reminder_shown'] = 1;
            echo $cc_reminder_modal;
        }
    }
    ?>
</body>

</html>
