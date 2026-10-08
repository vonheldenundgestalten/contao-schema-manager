# Repository workflow

Work directly on main for this extension. Create or use a separate branch only when the user explicitly requests it.

## Development deployment and handover

Always deploy extension changes to the development installation and test them there before handing them over to the user. Local checks alone are not a completed handover. Apply required additive database updates and rebuild the development cache as part of deployment. Verify the affected backend flow and frontend JSON-LD on dev, and report the results and any limitations. Do not publish to production or create a release tag unless requested.
