import Link from 'next/link';
import Image from 'next/image';
import styles from './DestinationCard.module.css';

interface FeaturedImage {
  node: {
    sourceUrl: string;
    altText: string;
  };
}

interface DestinationCardProps {
  destination: {
    databaseId: number;
    title: string;
    slug: string;
    excerpt: string;
    country: string;
    bestTimeToVisit: string;
    featuredImage?: FeaturedImage | null;
  };
}

export default function DestinationCard({ destination }: DestinationCardProps) {
  const { title, slug, excerpt, country, bestTimeToVisit, featuredImage } = destination;

  // Strip HTML tags from excerpt
  const cleanExcerpt = excerpt?.replace(/<[^>]*>/g, '') ?? '';

  return (
    <article className={styles.card}>
      <Link href={`/destinations/${slug}`} className={styles.imageLink}>
        <div className={styles.imageWrapper}>
          {featuredImage?.node ? (
            <Image
              src={featuredImage.node.sourceUrl}
              alt={featuredImage.node.altText || title}
              fill
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
              className={styles.image}
            />
          ) : (
            <div className={styles.placeholder}>
              <span className={styles.placeholderIcon}>🏔️</span>
            </div>
          )}

          {/* Country badge */}
          <span className={styles.countryBadge}>{country}</span>

          {/* Gradient overlay */}
          <div className={styles.imageOverlay} />
        </div>
      </Link>

      <div className={styles.content}>
        {bestTimeToVisit && (
          <span className={styles.seasonTag}>
            🗓️ Best time: {bestTimeToVisit}
          </span>
        )}

        <h3 className={styles.title}>
          <Link href={`/destinations/${slug}`}>{title}</Link>
        </h3>

        {cleanExcerpt && (
          <p className={styles.excerpt}>{cleanExcerpt}</p>
        )}

        <Link href={`/destinations/${slug}`} className={styles.exploreLink}>
          Explore
          <span className={styles.arrow}>→</span>
        </Link>
      </div>
    </article>
  );
}
