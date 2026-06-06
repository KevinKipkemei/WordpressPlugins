# Wanderlust Travel Journal (Headless WordPress + Next.js)

A decoupled, high-performance web application showcasing a headless CMS architecture. This repository contains both the frontend UI and the custom backend API integrations required to power the travel journal.

## Architecture Overview
* **Backend:** WordPress (Headless), acting purely as a content repository and data layer.
* **Frontend:** Next.js (App Router), responsible for server-side rendering, routing, and user interface.
* **API Layer:** WPGraphQL, bridging the two platforms together.

## Repository Structure
This repository contains the custom code written for this project:

* `/frontend` — The Next.js 15 application using React Server Components, TypeScript, and a bespoke Vanilla CSS design system (Glassmorphism, dark/light themes).
* `/backend/wp-plugins/kk-travel-journal-api` — A custom WordPress plugin that registers Custom Post Types (`Destinations` and `Trips`), creates relational Meta Fields, extends the WPGraphQL schema, and seeds mock data automatically upon activation.

*(Note: The WordPress core files and database are excluded to keep the repository focused on custom implementation.)*

## Features Highlights
* **Static Site Generation (SSG):** Pages are pre-rendered at build time via `generateStaticParams` for blazing-fast load times, with Incremental Static Regeneration (ISR) to keep data fresh.
* **Custom Data Models:** Implemented 1-to-Many relationships between Destinations and Trips using WordPress post metadata.
* **Type-Safe GraphQL:** The Next.js frontend fetches data securely via a custom typed API client using the standard `fetch` API.
* **Premium UI/UX:** Built entirely without utility frameworks (no Tailwind). All styles are managed via CSS Custom Properties, featuring responsive grid layouts, hover micro-interactions, and a sophisticated dark mode.

## Running Locally

### Backend Setup
1. Spin up a local WordPress environment (e.g., Local by Flywheel).
2. Install and activate the **WPGraphQL** plugin.
3. Drop the `/backend/wp-plugins/kk-travel-journal-api` folder into your `wp-content/plugins` directory and activate it. *(This will automatically seed the initial travel data!)*
4. Ensure your WordPress site's GraphQL endpoint is accessible (typically `http://yoursite.local/graphql`).

### Frontend Setup
1. Navigate to the frontend directory: `cd frontend`
2. Install dependencies: `npm install`
3. If necessary, update the `GRAPHQL_ENDPOINT` in `src/lib/api.ts` to match your local WordPress environment.
4. Run the development server: `npm run dev`
5. Open `http://localhost:3000` to view the application.


