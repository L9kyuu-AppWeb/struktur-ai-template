</main>

<!-- Footer -->
<footer class="bg-white border-t">
    <div class="px-6 py-6">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <div class="flex items-center space-x-2">
                <?php if (defined('COMPANY_ICON_PATH')): ?>
                    <?php
                    // Check if the specific company icon file exists
                    $expected_path = __DIR__ . '/../assets/images/company-icon.svg';
                    ?>
                    <?php if (file_exists($expected_path)): ?>
                        <img src="<?php echo COMPANY_ICON_PATH; ?>" alt="Logo Perusahaan" class="w-10 h-10 rounded-lg object-contain" onerror="this.style.display='none';">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="text-sm font-semibold text-gray-800"><?php echo defined('COMPANY_NAME') ? COMPANY_NAME : SITE_NAME; ?></p>
                    <p class="text-xs text-gray-500">© <?php
                        $currentYear = date('Y');
                        $startYear = COPYRIGHT_START_YEAR;
                        if ($currentYear == $startYear) {
                            echo $currentYear;
                        } else {
                            echo $startYear . '-' . $currentYear;
                        }
                    ?> All rights reserved</p>
                </div>
            </div>

            <div class="flex items-center space-x-6">
                <a href="#" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Tentang</a>
                <a href="#" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Bantuan</a>
                <a href="#" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Privasi</a>
            </div>
        </div>
    </div>
</footer>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    function toggleProfileMenu() {
        const menu = document.getElementById('profileMenu');
        menu.classList.toggle('hidden');
    }

    // Close profile menu when clicking outside
    document.addEventListener('click', function(event) {
        const profileMenu = document.getElementById('profileMenu');
        const profileButton = event.target.closest('button[onclick="toggleProfileMenu()"]');

        if (!profileButton && !profileMenu.contains(event.target)) {
            profileMenu.classList.add('hidden');
        }
    });

    // Close sidebar on mobile when clicking menu item
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                toggleSidebar();
            }
        });
    });

    function confirmDelete(message) {
        return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
    }
</script>
</body>

</html>