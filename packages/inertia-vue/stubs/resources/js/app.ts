import { bootstrapMarkoInertiaVue } from "../../vendor/marko/inertia-vue/resources/js/bootstrap";

const pages = import.meta.glob([
  "./pages/**/*.vue",
  "../../app/**/resources/js/pages/**/*.vue",
  "../../modules/**/resources/js/pages/**/*.vue",
  "../../vendor/marko/**/resources/js/pages/**/*.vue",
]);

bootstrapMarkoInertiaVue({ pages });
