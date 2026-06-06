import Link from 'next/link';
import Image from 'next/image';
import styles from './TripCard.module.css';

interface FeaturedImage {
  node: {
    sourceUrl: string;
    altText: string;
  };
}

interface TripCardProps {
  trip: {
    databaseId: number;
    title: string;
    slug: string;
    excerpt: string;
    travelDate: string;
    durationDays: number;
    costUsd: number;
    difficulty: string;
    featuredImage?: FeaturedImage | null;
    destination?: {
      title: string;
      slug: string;
      country: string;
    };
  };
}

function getDifficultyClass(difficulty: string): string {
  switch (difficulty?.toLowerCase()) {
    case 'easy':
      return styles.difficultyEasy;
    case 'moderate':
      return styles.difficultyModerate;
    case 'challenging':
      return styles.difficultyChallenging;
    default:
      return styles.difficultyEasy;
  }
}

function formatDate(dateString: string): string {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  } catch {
    return dateString;
  }
}

function formatCost(cost: number): string {
  if (!cost && cost !== 0) return '';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(cost);
}

export default function TripCard({ trip }: TripCardProps) {
  const {
    title,
    slug,
    excerpt,
    travelDate,
    durationDays,
    costUsd,
    difficulty,
    featuredImage,
    destination,
  } = trip;

  const cleanExcerpt = excerpt?.replace(/<[^>]*>/g, '') ?? '';

  return (
    <article className={styles.card}>
      <Link href={`/trips/${slug}`} className={styles.imageLink}>
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
              <span className={styles.placeholderIcon}>🧭</span>
            </div>
          )}

          {/* Difficulty badge */}
          {difficulty && (
            <span className={`${styles.difficultyBadge} ${getDifficultyClass(difficulty)}`}>
              {difficulty}
            </span>
          )}

          {/* Overlay */}
          <div className={styles.imageOverlay} />
        </div>
      </Link>

      <div className={styles.content}>
        {/* Meta info row */}
        <div className={styles.metaRow}>
          {travelDate && (
            <span className={styles.metaItem} title="Travel date">
              📅 {formatDate(travelDate)}
            </span>
          )}
          {durationDays > 0 && (
            <span className={styles.metaItem} title="Duration">
              ⏱️ {durationDays} day{durationDays !== 1 ? 's' : ''}
            </span>
          )}
          {(costUsd > 0 || costUsd === 0) && (
            <span className={styles.metaItem} title="Cost">
              💰 {formatCost(costUsd)}
            </span>
          )}
        </div>

        <h3 className={styles.title}>
          <Link href={`/trips/${slug}`}>{title}</Link>
        </h3>

        {destination && (
          <Link
            href={`/destinations/${destination.slug}`}
            className={styles.destinationLink}
          >
            📍 {destination.title}
            {destination.country ? `, ${destination.country}` : ''}
          </Link>
        )}

        {cleanExcerpt && (
          <p className={styles.excerpt}>{cleanExcerpt}</p>
        )}

        <Link href={`/trips/${slug}`} className={styles.readMoreLink}>
          Read More
          <span className={styles.arrow}>→</span>
        </Link>
      </div>
    </article>
  );
}
