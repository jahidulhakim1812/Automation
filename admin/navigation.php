<aside class="sidebar" id="sidebar">
    <a href="dashboard.php"><?php echo get_sidebar_label('dashboard', '📊 Dashboard'); ?></a>
    <div class="sidebar-divider"></div>

    <!-- Account Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('account', '💵 Account'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="account.php"><?php echo get_sidebar_label('account_overview', 'Account Overview'); ?></a>
            <a href="account_report.php"><?php echo get_sidebar_label('account_report', 'Account Report'); ?></a>
            <a href="change_password.php"><?php echo get_sidebar_label('change_password', 'Change Password'); ?></a>
        </div>
    </div>

    <!-- Student Info Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('student_info', '👤 Student Info'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="insert.php"><?php echo get_sidebar_label('add_student', 'Add Student'); ?></a>
            <a href="student_list.php"><?php echo get_sidebar_label('total_student_list', 'Total Student List'); ?></a>
            <a href="form_view.php"><?php echo get_sidebar_label('student_form', 'Student Form'); ?></a>
            <a href="completed_students.php"><?php echo get_sidebar_label('course_complete', 'Course Complete'); ?></a>
            <a href="incomplete_students.php"><?php echo get_sidebar_label('course_incomplete', 'Course Incomplete'); ?></a>
            <a href="blocked_students_list.php"><?php echo get_sidebar_label('Blocked_Student_List', 'Blocked Student List'); ?></a>
            <a href="ongoing_students.php"><?php echo get_sidebar_label('ongoing', 'Ongoing'); ?></a>
        </div>
    </div>

    <!-- Customers Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('customers', '👥 Customers'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="add_customer.php"><?php echo get_sidebar_label('add_customer', 'Add Customer'); ?></a>
            <a href="customer_list.php"><?php echo get_sidebar_label('customer_list', 'Customer List'); ?></a>
        </div>
    </div>

    <!-- Services Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('services', '🛠️ Services'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="services.php"><?php echo get_sidebar_label('manage_services', 'Manage Services'); ?></a>
            <a href="assign_service.php"><?php echo get_sidebar_label('assign_service', 'Assign Service'); ?></a>
            <a href="invoice_list.php"><?php echo get_sidebar_label('invoice_list', 'Invoice List'); ?></a>
            <a href="customer_due_list.php"><?php echo get_sidebar_label('customer_due_list', 'Customer Due List'); ?></a>
            <a href="customer_payment.php"><?php echo get_sidebar_label('customer_payments', 'Customer Payments'); ?></a>
             <a href="customer_purchases.php"><?php echo get_sidebar_label('customer_purchases', 'Customer Purchases'); ?></a>
        </div>
    </div>

      <!-- expense Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('expense', '💰 Expense'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="add_expense.php"><?php echo get_sidebar_label('add_expense', 'Add Expense'); ?></a>
            <a href="expense_list.php"><?php echo get_sidebar_label('expense_list', 'Expense List'); ?></a>
            
        </div>
    </div>

        <a href="notices.php"><?php echo get_sidebar_label('notices', '📢 Notices'); ?></a>

    <a href="delete.php"><?php echo get_sidebar_label('delete', '🗑️ Delete'); ?></a>
    <a href="report.php"><?php echo get_sidebar_label('report', '📄 Report'); ?></a>
    <a href="bulk_email.php"><?php echo get_sidebar_label('Bulk_Email', '✉️ Bulk Email'); ?></a>

    <!-- Payment Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('payment', '💵 Payment'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
        
            <a href="invoice_list.php"><?php echo get_sidebar_label('invoice_list', 'Invoice List'); ?></a>
            <a href="invoice.php"><?php echo get_sidebar_label('print_invoice', 'Print Invoice'); ?></a>
            <a href="view_invoice.php"><?php echo get_sidebar_label('verify_invoice', 'Verify Invoice'); ?></a>
            <a href="input_payment.php"><?php echo get_sidebar_label('add_payment', 'Add Payment'); ?></a>
            <a href="payment_due.php"><?php echo get_sidebar_label('due_payment_list', 'Due Payment List'); ?></a>
             <a href="whatsapp_due.php"><?php echo get_sidebar_label('send_whatsapp_message', 'Send Whatsapp Message'); ?></a>
        </div>
    </div>

    <!-- Attendance Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('attendance', '📆 Attendance'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="attendance.php"><?php echo get_sidebar_label('take_attendance', 'Take Attendance'); ?></a>
            <a href="attendance_report.php"><?php echo get_sidebar_label('attendance_report', 'View Report'); ?></a>
        </div>
    </div>

    <!-- Certificate Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('certificate', '🎓 Certificate'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="upload_certificate.php"><?php echo get_sidebar_label('upload_certificate', 'Upload Certificate'); ?></a>
            <a href="certificate_list.php"><?php echo get_sidebar_label('view_certificate', 'View Certificate'); ?></a>
            <a href="generate_certificate.php"><?php echo get_sidebar_label('generate_certificate', 'Generate Certificate'); ?></a>
        </div>
    </div>

    <!-- Video Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('video', '🎬 Video'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="upload_video.php"><?php echo get_sidebar_label('upload_video', 'Upload Video'); ?></a>
            <a href="view_videos.php"><?php echo get_sidebar_label('view_videos', 'View Videos'); ?></a>
        </div>
    </div>
    <!-- Routine Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('routine', '🕒 Routine'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="routine_generator.php"><?php echo get_sidebar_label('make_routine', 'Make Routine'); ?></a>
            <a href="rutine_form.php"><?php echo get_sidebar_label('print_routine', 'Print Routine'); ?></a>
        </div>
    </div>

     <!-- Ip track  -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('iplocator', '📍 IP Geolocation'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="admin_logs.php" class="active"><?php echo get_sidebar_label('admin_logs', 'Activity Log'); ?></a>
    <a href="ip_locator.php" class="active"><?php echo get_sidebar_label('ip_locator', 'IP Locator'); ?></a>
        </div>
    </div>

    <a href="settings.php"><?php echo get_sidebar_label('settings', '⚙️ Settings'); ?></a>
    
</aside>