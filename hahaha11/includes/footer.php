</main>
    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>Career Guidance System</h3>
                    <p>Connecting businesses with expert consultants to drive growth and success.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="consultants.php">Consultants</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Business Street, City, Country</p>
                    <p><i class="fas fa-phone"></i> +1 234 567 8900</p>
                    <p><i class="fas fa-envelope"></i> info@careerguidance.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Career Guidance System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="/assets/script.js"></script>
    <style>
    .footer {
        background: var(--text-color);
        color: var(--white);
        padding: 60px 0 20px;
        margin-top: 80px;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        margin-bottom: 40px;
    }

    .footer-section h3 {
        color: var(--white);
        font-size: 1.5rem;
        margin-bottom: 20px;
        position: relative;
    }

    .footer-section h3::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -10px;
        width: 50px;
        height: 2px;
        background: var(--primary-color);
    }

    .footer-section p {
        color: #94a3b8;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .social-links {
        margin-top: 20px;
    }

    .social-links a {
        display: inline-block;
        width: 35px;
        height: 35px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        text-align: center;
        line-height: 35px;
        color: var(--white);
        margin-right: 10px;
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: var(--primary-color);
        transform: translateY(-3px);
    }

    .footer-section.links ul {
        list-style: none;
        padding: 0;
    }

    .footer-section.links ul li {
        margin-bottom: 10px;
    }

    .footer-section.links ul li a {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .footer-section.links ul li a:hover {
        color: var(--primary-color);
        padding-left: 5px;
    }

    .footer-section.contact p {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .footer-section.contact p i {
        margin-right: 10px;
        color: var(--primary-color);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-bottom p {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .footer-section h3::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .social-links {
            justify-content: center;
        }
    }
    </style>
</body>
</html>