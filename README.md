
# Explanation of Approach and Trade-offs

### Project Overview
This is a content scheduling platform whereby a user would post once and
publish to various platforms (e.g., Facebook, LinkedIn, etc.). Users would have
the ability to switch on/off which platforms they want to publish to, offering
flexibility and independence.

### Tech Stack and Why I Chose It
Laravel 12: Utilized as the backend framework because of its expressive syntax,
out-of-the-box validation, job queues, and high-level ORM (Eloquent).

Vue 3: Blended with Inertia.js for a reactive, contemporary user experience without the need to construct a full SPA.

Inertia.js: A connector for Vue and Laravel that gives server-side routing with a
frontend SPA-like experience and less boilerplate code.

**Trade-off** : Inertia speeds up development and avoids API boilerplate but binds the frontend and backend strongly together, which can lead to inflexibility in multi- client environments (e.g., mobile apps).

### Architecture Decisions
**Single Form to Post:** Created a dynamic form with platform toggles such that the
user can post to numerous platforms in one go.

**Queued Publication Jobs:** Used Laravel's job system to publish posts on distant
sites in the background while maintaining the app's responsiveness and efficiency.

**Platform Toggles Design:** Every platform has a toggle, which loads platform-
selection fields conditionally (e.g., Instagram, LinkedIn selection).

**Trade-off:** Keeping all platform-specific logic in one place made the code
complex. I simplified it by splitting form elements into smaller components and
reusing logic wherever I could.

### Validation and Feedback

Applied Laravel's form request validation to make required fields dependent on
selected platforms.

Vue employed reactive bindings to perform live form validation and UX feedback.

**Trade-off:** Conditionally validating rules depending on flipped platforms
introduced additional logic but provided improved UX.

### Seeders

Provided seeders to quickly set up platforms, making the development process
faste
