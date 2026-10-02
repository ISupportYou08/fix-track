---
paths:
  - 'app/Actions/Bookings/AnalyzeServiceItemImage.php,config/services.php,tests/Feature/AiAssistedBookingTest.php'
---

# Bookings Feature

## Use Cloudflare vision for item analysis
The customer AI item scanner calls Cloudflare Workers AI with CLOUDFLARE_API_TOKEN, CLOUDFLARE_ACCOUNT_ID, and CLOUDFLARE_VISION_MODEL. Use @cf/meta/llama-3.2-11b-vision-instruct for recognition; FLUX models generate or edit images and do not replace this analysis flow. Cloudflare may prefix its JSON with prose, so extract and validate the catalog-bound JSON object before accepting a service code.
