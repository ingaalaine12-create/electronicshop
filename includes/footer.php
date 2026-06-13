<?php
// includes/footer.php
// Elegant responsive footer with company credentials and localized context
?>
    <!-- Corporate Premium Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: Brand details -->
                <div class="footer-col">
                    <h3 class="footer-title" style="font-size: 20px; font-weight:800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display:inline-block; margin-bottom: 16px;">
                        Kigali TechHub
                    </h3>
                    <p style="margin-bottom: 20px; line-height: 1.6; font-size: 14px;">
                        Kigali's ultimate technology destination. We pride ourselves on supplying elite electronics, professional diagnostic repairs, and precision custom PC assembly.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-icon" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="social-icon" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-col">
                    <h4 class="footer-title">Product Catalog</h4>
                    <ul class="footer-links">
                        <li><a href="index.php?cat=laptops">Laptops & Workstations</a></li>
                        <li><a href="index.php?cat=smartphones">Smartphones & Tablets</a></li>
                        <li><a href="index.php?cat=audio">Premium Sound & Audio</a></li>
                        <li><a href="index.php?cat=wearables">Wearables & Sports Fit</a></li>
                    </ul>
                </div>

                <!-- Column 3: Services & Support -->
                <div class="footer-col">
                    <h4 class="footer-title">Our Services</h4>
                    <ul class="footer-links">
                        <li><a href="index.php#services">Diagnostic Repairs</a></li>
                        <li><a href="index.php#services">Custom System Assemblies</a></li>
                        <li><a href="cart.php">Shopping Cart</a></li>
                        <li><a href="admin.php">Admin Panel</a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact details -->
                <div class="footer-col">
                    <h4 class="footer-title">Visit Our Shop</h4>
                    <ul class="footer-links" style="font-size:13.5px;">
                        <li style="margin-bottom: 10px;">
                            <i class="fa-solid fa-map-location-dot" style="color: var(--accent-primary); margin-right: 8px;"></i>
                            Kigali Heights, Floor 3, Block B<br>KG 7 Ave, Kigali, Rwanda
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fa-solid fa-phone" style="color: var(--accent-primary); margin-right: 8px;"></i>
                            +250 788 123 456
                        </li>
                        <li>
                            <i class="fa-solid fa-envelope" style="color: var(--accent-primary); margin-right: 8px;"></i>
                            support@kigalitechhub.rw
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom Bar -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Kigali TechHub Ltd. All rights reserved.</p>
                <div class="payment-methods" style="display: flex; gap: 10px; align-items: center; font-size: 12px; color: var(--text-muted);">
                    <span>Supported Payments:</span>
                    <i class="fa-solid fa-mobile-screen" style="color: #facc15;" title="MTN Mobile Money"></i> MoMo
                    <i class="fa-solid fa-mobile-screen" style="color: #ef4444;" title="Airtel Money"></i> Airtel
                    <i class="fa-regular fa-credit-card" style="color: #3b82f6;" title="Visa/MasterCard"></i> Cards
                    <i class="fa-solid fa-hand-holding-dollar" style="color: var(--success);" title="Cash on Delivery"></i> Cash
                </div>
            </div>
        </div>
    </footer>

    <!-- App JavaScript -->
    <script src="assets/js/app.js"></script>
</body>
</html>
