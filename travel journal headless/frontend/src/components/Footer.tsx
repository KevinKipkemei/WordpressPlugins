import Link from 'next/link';
import styles from './Footer.module.css';

const QUICK_LINKS = [
  { href: '/', label: 'Home' },
  { href: '/destinations', label: 'Destinations' },
  { href: '/trips', label: 'Trips' },
];

export default function Footer() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className={styles.footer}>
      {/* Gradient top border */}
      <div className={styles.gradientBorder} />

      <div className={styles.container}>
        <div className={styles.grid}>
          {/* About column */}
          <div className={styles.column}>
            <h3 className={styles.columnTitle}>
              <span className={styles.titleIcon}>🌍</span> Wanderlust
            </h3>
            <p className={styles.aboutText}>
              Documenting journeys across trails, temples, and hidden corners of
              the earth. Every destination tells a story — these are ours.
            </p>
          </div>

          {/* Quick Links column */}
          <div className={styles.column}>
            <h3 className={styles.columnTitle}>Quick Links</h3>
            <ul className={styles.linkList}>
              {QUICK_LINKS.map((link) => (
                <li key={link.href}>
                  <Link href={link.href} className={styles.footerLink}>
                    <span className={styles.linkArrow}>→</span>
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Connect column */}
          <div className={styles.column}>
            <h3 className={styles.columnTitle}>Connect</h3>
            <ul className={styles.linkList}>
              <li>
                <a
                  href="https://github.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className={styles.footerLink}
                >
                  <span className={styles.linkArrow}>→</span>
                  GitHub
                </a>
              </li>
              <li>
                <a
                  href="https://twitter.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className={styles.footerLink}
                >
                  <span className={styles.linkArrow}>→</span>
                  Twitter
                </a>
              </li>
              <li>
                <a
                  href="mailto:hello@wanderlust.com"
                  className={styles.footerLink}
                >
                  <span className={styles.linkArrow}>→</span>
                  Email
                </a>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom bar */}
        <div className={styles.bottomBar}>
          <p className={styles.copyright}>
            © {currentYear} Wanderlust. All rights reserved.
          </p>
          <p className={styles.madeWith}>
            Made with 🤍 &amp; Next.js
          </p>
        </div>
      </div>
    </footer>
  );
}
