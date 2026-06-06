// ============================================
// GraphQL API Client — Wanderlust Journal
// ============================================

const GRAPHQL_ENDPOINT = 'http://127.0.0.1:10011/graphql';

// ---------- TypeScript Interfaces ----------

export interface FeaturedImage {
  node: {
    sourceUrl: string;
    altText: string;
  };
}

export interface Destination {
  databaseId: number;
  title: string;
  slug: string;
  excerpt: string;
  content?: string;
  country: string;
  bestTimeToVisit: string;
  featuredImage: FeaturedImage | null;
  trips?: Trip[];
}

export interface Trip {
  databaseId: number;
  title: string;
  slug: string;
  excerpt: string;
  content?: string;
  travelDate: string;
  durationDays: number;
  costUsd: number;
  difficulty: string;
  featuredImage: FeaturedImage | null;
  destination?: {
    title: string;
    slug: string;
    country: string;
  };
}

// ---------- Base GraphQL Fetcher ----------

export async function fetchGraphQL<T = unknown>(
  query: string,
  variables?: Record<string, unknown>
): Promise<T> {
  const res = await fetch(GRAPHQL_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ query, variables }),
    next: { revalidate: 60 },
  });

  if (!res.ok) {
    throw new Error(`GraphQL request failed: ${res.status} ${res.statusText}`);
  }

  const json = await res.json();

  if (json.errors) {
    console.error('GraphQL errors:', JSON.stringify(json.errors, null, 2));
    throw new Error(
      `GraphQL errors: ${json.errors.map((e: { message: string }) => e.message).join(', ')}`
    );
  }

  return json.data as T;
}

// ---------- Destination Queries ----------

const DESTINATION_CARD_FIELDS = `
  databaseId
  title
  slug
  excerpt
  country
  bestTimeToVisit
  featuredImage {
    node {
      sourceUrl
      altText
    }
  }
`;

const DESTINATION_FULL_FIELDS = `
  databaseId
  title
  slug
  excerpt
  content
  country
  bestTimeToVisit
  featuredImage {
    node {
      sourceUrl
      altText
    }
  }
  trips {
    databaseId
    title
    slug
    excerpt
    travelDate
    durationDays
    costUsd
    difficulty
    featuredImage {
      node {
        sourceUrl
        altText
      }
    }
  }
`;

export async function getAllDestinations(): Promise<Destination[]> {
  const query = `
    query GetAllDestinations {
      destinations(first: 100) {
        nodes {
          ${DESTINATION_CARD_FIELDS}
        }
      }
    }
  `;

  const data = await fetchGraphQL<{ destinations: { nodes: Destination[] } }>(query);
  return data.destinations.nodes;
}

export async function getDestinationBySlug(slug: string): Promise<Destination | null> {
  const query = `
    query GetDestinationBySlug($slug: ID!) {
      destination(id: $slug, idType: SLUG) {
        ${DESTINATION_FULL_FIELDS}
      }
    }
  `;

  const data = await fetchGraphQL<{ destination: Destination | null }>(query, { slug });
  return data.destination;
}

// ---------- Trip Queries ----------

const TRIP_CARD_FIELDS = `
  databaseId
  title
  slug
  excerpt
  travelDate
  durationDays
  costUsd
  difficulty
  featuredImage {
    node {
      sourceUrl
      altText
    }
  }
  destination {
    ... on Destination {
      title
      slug
      country
    }
  }
`;

const TRIP_FULL_FIELDS = `
  databaseId
  title
  slug
  excerpt
  content
  travelDate
  durationDays
  costUsd
  difficulty
  featuredImage {
    node {
      sourceUrl
      altText
    }
  }
  destination {
    ... on Destination {
      databaseId
      title
      slug
      country
      bestTimeToVisit
      featuredImage {
        node {
          sourceUrl
          altText
        }
      }
    }
  }
`;

export async function getAllTrips(): Promise<Trip[]> {
  const query = `
    query GetAllTrips {
      trips(first: 100, where: { orderby: { field: DATE, order: DESC } }) {
        nodes {
          ${TRIP_CARD_FIELDS}
        }
      }
    }
  `;

  const data = await fetchGraphQL<{ trips: { nodes: Trip[] } }>(query);
  return data.trips.nodes;
}

export async function getTripBySlug(slug: string): Promise<Trip | null> {
  const query = `
    query GetTripBySlug($slug: ID!) {
      trip(id: $slug, idType: SLUG) {
        ${TRIP_FULL_FIELDS}
      }
    }
  `;

  const data = await fetchGraphQL<{ trip: Trip | null }>(query, { slug });
  return data.trip;
}

export async function getFeaturedTrips(first: number = 3): Promise<Trip[]> {
  const query = `
    query GetFeaturedTrips($first: Int!) {
      trips(first: $first, where: { orderby: { field: DATE, order: DESC } }) {
        nodes {
          ${TRIP_CARD_FIELDS}
        }
      }
    }
  `;

  const data = await fetchGraphQL<{ trips: { nodes: Trip[] } }>(query, { first });
  return data.trips.nodes;
}

// ---------- Utility Helpers ----------

export function getDifficultyBadgeClass(difficulty: string): string {
  const normalized = difficulty?.toLowerCase() ?? '';
  switch (normalized) {
    case 'easy':
      return 'badge--easy';
    case 'moderate':
      return 'badge--moderate';
    case 'hard':
      return 'badge--hard';
    case 'extreme':
      return 'badge--extreme';
    default:
      return 'badge--green';
  }
}

export function formatTravelDate(dateString: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

export function formatCost(cost: number): string {
  if (!cost && cost !== 0) return '';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(cost);
}
