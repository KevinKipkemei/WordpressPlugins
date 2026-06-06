import Link from 'next/link';
import styles from './Hero.module.css';

export default function Hero() {
  return (
    <section className={styles.hero}>
      {/* Animated background gradient */}
      <div className={styles.gradientBg} />

      {/* Floating decorative elements */}
      <div className={styles.decorations}>
        <div className={`${styles.blob} ${styles.blob1}`} />
        <div className={`${styles.blob} ${styles.blob2}`} />
        <div className={`${styles.blob} ${styles.blob3}`} />
        <div className={`${styles.blob} ${styles.blob4}`} />
      </div>

      {/* Content */}
      <div className={styles.content}>
        <h1 className={styles.heading}>
          Explore the <span className={styles.headingAccent}>World</span>
        </h1>

        <p className={styles.subtitle}>
          Stories from trails, temples, and hidden corners of the earth
        </p>

        <div className={styles.ctas}>
          <Link href="/destinations" className={styles.ctaPrimary}>
            Browse Destinations
            <span className={styles.ctaArrow}>→</span>
          </Link>
          <Link href="/trips" className={styles.ctaSecondary}>
            View Trips
            <span className={styles.ctaArrow}>→</span>
          </Link>
        </div>
      </div>

      {/* Bottom gradient fade */}
      <div className={styles.bottomFade} />
    </section>
  );
}
