'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import styles from './Header.module.css';

const NAV_LINKS = [
  { href: '/', label: 'Home' },
  { href: '/destinations', label: 'Destinations' },
  { href: '/trips', label: 'Trips' },
];

export default function Header() {
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  // Close mobile menu on route change
  useEffect(() => {
    setMobileOpen(false);
  }, [pathname]);

  // Prevent body scroll when mobile menu is open
  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : '';
    return () => { document.body.style.overflow = ''; };
  }, [mobileOpen]);

  const isActive = (href: string) => {
    if (href === '/') return pathname === '/';
    return pathname.startsWith(href);
  };

  return (
    <header className={`${styles.header} ${scrolled ? styles.scrolled : ''}`}>
      <nav className={styles.nav}>
        <Link href="/" className={styles.logo}>
          <span className={styles.logoIcon}>🌍</span>
          <span className={styles.logoText}>Wanderlust</span>
        </Link>

        {/* Desktop navigation */}
        <ul className={styles.navLinks}>
          {NAV_LINKS.map((link) => (
            <li key={link.href}>
              <Link
                href={link.href}
                className={`${styles.navLink} ${isActive(link.href) ? styles.active : ''}`}
              >
                {link.label}
                <span className={styles.linkUnderline} />
              </Link>
            </li>
          ))}
        </ul>

        {/* Hamburger button */}
        <button
          className={`${styles.hamburger} ${mobileOpen ? styles.hamburgerOpen : ''}`}
          onClick={() => setMobileOpen((prev) => !prev)}
          aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
          aria-expanded={mobileOpen}
        >
          <span className={styles.hamburgerLine} />
          <span className={styles.hamburgerLine} />
          <span className={styles.hamburgerLine} />
        </button>
      </nav>

      {/* Mobile overlay */}
      <div
        className={`${styles.mobileOverlay} ${mobileOpen ? styles.mobileOverlayOpen : ''}`}
        onClick={() => setMobileOpen(false)}
        aria-hidden="true"
      />

      {/* Mobile slide-in menu */}
      <div className={`${styles.mobileMenu} ${mobileOpen ? styles.mobileMenuOpen : ''}`}>
        <ul className={styles.mobileNavLinks}>
          {NAV_LINKS.map((link, index) => (
            <li
              key={link.href}
              className={styles.mobileNavItem}
              style={{ transitionDelay: mobileOpen ? `${index * 80 + 150}ms` : '0ms' }}
            >
              <Link
                href={link.href}
                className={`${styles.mobileNavLink} ${isActive(link.href) ? styles.active : ''}`}
              >
                {link.label}
              </Link>
            </li>
          ))}
        </ul>
      </div>

      {/* Gradient bottom border */}
      <div className={styles.gradientBorder} />
    </header>
  );
}
