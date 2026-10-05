# Round 2: 100 complex-offering use cases

[Round 2 proposal](round-2.md) · [Feedback](feedback/01-round-2.md)

Requested by D01: broaden the design beyond our current clients. These are **100 illustrative use cases, not 100 promised new Schema.org classes**. Most complex offerings legitimately share Product, ProductModel, SoftwareApplication or Service. Industry labels, serviceType/category and meaningful capabilities provide specificity without fabricated types. The catalogue tests coverage; it does not prove exact client requirements or approve implementation.

Excluded: ordinary cart-based retail, checkout, stock management, payments, price extraction and course catalogues. Supporting places and entities are included where needed to explain high-value purchases or enquiries. Conditional mappings require further examples. Schema.org type links below are vocabulary references, not claims of Google rich-result eligibility.

A broad catalogue should guide editors through searchable names and suitable field groups. It should not require 100 individually coded forms or expose all fields at once. Distinguish **vocabulary recognition**, **editable capabilities**, and **source integration**: recognizing a type alone does not mean all its properties can be edited or mapped.

## Buildings and property

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U001 | Custom detached house design | ProductModel | Dimensions, construction method, energy specification | Separate built examples and site-specific offers |
| U002 | Apartment building design | ProductModel | Units, floor-area definition, construction variants | Not the completed building |
| U003 | Modular school building | ProductModel | Capacity, modules, accessibility specifications | Installation and operation are separate services |
| U004 | Industrial hall system | ProductModel | Span, loads, materials | Configured project is not another generic model |
| U005 | Office building for lease | Place | Address, areas, facilities | Offer and occupancy facts require a property-specific adapter |
| U006 | Commercial development plot | Place | Address, plot area, permitted-use evidence | No guessed development rights |
| U007 | Completed house for sale | House + Product (conditional) | Address, physical facts, actual sales offer | Multiple types only for the same offered physical house |
| U008 | Senior-living residence | Residence | Location, facilities, accommodation | Care provider and care services remain separate |
| U009 | Student accommodation complex | Residence | Location, facilities, unit types | Separate residence from rental offers |
| U010 | Show house used for consultations | House | Address, visiting arrangements, model link | Supporting venue rather than automatically a sale item |

## Building systems and site services

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U011 | Commercial heat-pump system | ProductModel | Capacity, operating conditions, certifications | Sizing/design service separate |
| U012 | Building-integrated solar system | Product | Components, rated output, installation context | Do not infer a fixed bundle quantity |
| U013 | Commercial battery storage system | ProductModel | Capacity, power, safety evidence | Model versus commissioned installation |
| U014 | Facade system | Product | Materials, application limits, fire classification evidence | Certification scope matters |
| U015 | Hospital flooring system | Product | Thickness, constituents, cleaning properties | Claims require evidence |
| U016 | Sports flooring system | Product | Layers, surface properties, standards | Certificate belongs to system |
| U017 | Industrial waterproofing system | Product | Materials, use conditions, test evidence | Do not inherit component approvals |
| U018 | Building retrofit programme | Service | Provider, coverage, project scope | Assessment does not guarantee savings |
| U019 | Turnkey building construction | Service | Provider, area served, scope | House model and construction service can coexist |
| U020 | Facilities maintenance contract | Service | Provider, covered facilities, service scope | Contract price stays manual/source-owned |

## Industrial equipment and production

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U021 | CNC machining centre | ProductModel | Axes, travel, precision specification | Individual used machine has separate identity |
| U022 | Robotic production cell | Product | Constituents, throughput conditions, safety evidence | Integrated system versus component robots |
| U023 | Packaging line | ProductModel | Supported formats, rated throughput | Not a fixed guarantee across every configuration |
| U024 | Industrial pump station | Product | Components, duty range, materials | Engineered system with variable configuration |
| U025 | Laboratory analyser | ProductModel | Methods, capacity, certifications | Medical claims need appropriate reviewed modelling |
| U026 | Commercial 3D printer | ProductModel | Build volume, supported materials | Consumables need not all become entities |
| U027 | Industrial filtration system | ProductModel | Flow range, media, application | Performance under defined conditions |
| U028 | Bespoke production tooling | Product | Material, application, technical requirements | Quote-based custom deliverable |
| U029 | Machine retrofit service | Service | Provider, machine scope, coverage | Separate from resulting hardware |
| U030 | Contract manufacturing | Service | Processes, provider, capacities | Capabilities are not automatically products |

## Infrastructure and energy

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U031 | Wind turbine model | ProductModel | Rated power, dimensions, conditions | Installed site separate |
| U032 | Commercial EV charging system | Product | Components, power, supported interfaces | Charging operation may be another service |
| U033 | Water treatment plant package | Product | Process configuration, capacity | Physical operating plant separate |
| U034 | Waste sorting line | ProductModel | Materials handled, throughput | Project-specific configuration |
| U035 | District heating installation | Service | Design/build scope, provider, region | Equipment can have separate product identities |
| U036 | Energy performance contracting | Service | Provider, scope, evidence-backed terms | No fabricated financial guarantees |
| U037 | Grid connection engineering | Service | Provider, area served, scope | Approval responsibility must be accurate |
| U038 | Environmental remediation | Service | Provider, methods, regions | Site-specific claims remain on the project |
| U039 | Telecom infrastructure deployment | Service | Provider, scope, coverage | Network asset versus delivery service |
| U040 | Industrial energy audit | Service | Provider, scope, qualifications | Audit is not itself a certification |

## Enterprise software and digital operations

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U041 | Document-management platform | SoftwareApplication | Requirements, features, publisher | Preserve existing software identity |
| U042 | Enterprise resource planning software | SoftwareApplication | Supported environments, modules | Modules only separate when meaningfully offered |
| U043 | Industry-specific CRM | SoftwareApplication | Requirements, audience, features | Implementation service separate |
| U044 | Manufacturing execution system | SoftwareApplication | Requirements, interfaces, capabilities | Hardware integrations do not imply ownership |
| U045 | Building information modelling platform | SoftwareApplication | Requirements, supported workflows | Project documents remain content |
| U046 | Enterprise AI search solution | SoftwareApplication | Deployment requirements, supported functions | No invented accuracy claims |
| U047 | Managed private cloud | Service | Provider, scope, region | Do not infer data location from provider address |
| U048 | Dedicated hosting offer | Service | Provider, resource/service specifications | Manual offers; no new pricing extraction |
| U049 | Cybersecurity monitoring | Service | Provider, scope, coverage | Security claims need supporting evidence |
| U050 | Software migration project | Service | Provider, supported source/target systems | Each case is evidence, not another service |

## Professional and business services

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U051 | Corporate turnaround consulting | Service | Provider, expertise, area served | Cases describe delivered work |
| U052 | Mergers and acquisitions advisory | Service | Provider, scope, sector expertise | No automatic financial-product type |
| U053 | Tax advisory engagement | Service | Provider, scope, public contacts | LocalBusiness sufficient; no AccountingService requirement |
| U054 | Commercial legal advisory | Service | Provider, scope, jurisdiction | Do not infer professional credentials |
| U055 | Engineering consultancy | Service | Provider, disciplines, regions | Responsible expert is not always sole provider |
| U056 | Architecture commission | Service | Provider, project scope, region | Completed building remains a different entity |
| U057 | Executive recruitment | Service | Provider, sector scope | Open positions are JobPosting content |
| U058 | Brand strategy engagement | Service | Provider, scope, relevant cases | Campaign outputs not automatically services |
| U059 | Accessibility consulting | Service | Provider, scope, audit methodology | Assessment versus certificate distinction |
| U060 | Maintenance and support agreement | Service | Provider, channels, coverage | Territorial support contact is not a translation fact |

## Healthcare and care provision

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U061 | Hospital care offering | Service | Provider, service scope, public contact | Hospital entity separate; clinical detail requires review |
| U062 | Specialist outpatient consultation | Service | Provider, location, specialty context | Do not infer emergency availability |
| U063 | Diagnostic laboratory service | Service | Provider, scope, location | MedicalTest only if describing an actual test |
| U064 | Imaging service | Service | Provider, locations, appointment route | Modality/procedure modelling requires medical evidence |
| U065 | Rehabilitation programme | Service | Provider, location, scope | Clinical pathway is not automatically Course |
| U066 | Physiotherapy service | Service | Provider, location, scope | Specific treatment types remain a separate decision |
| U067 | Home nursing | Service | Provider, coverage, contact | Provider office is not patient location |
| U068 | Residential nursing care | Service | Provider, location, care scope | Residence and operator can differ |
| U069 | Occupational health service | Service | Provider, audience, scope | Employer client is not provider |
| U070 | Medical device installation and maintenance | Service | Provider, equipment scope, region | Separate equipment model and maintenance offering |

## Mobility and logistics

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U071 | Commercial vehicle model | Vehicle | Capacity, dimensions, propulsion | Do not attach stock-unit mileage to model family |
| U072 | Configured fleet vehicle | Vehicle | Configuration, identity, offer | Stock unit and model separate |
| U073 | Municipal specialist vehicle | Vehicle | Equipment, capacity, intended use | Body and chassis relationships need real examples |
| U074 | Passenger ferry | Vehicle | Capacity, dimensions, propulsion | Vessel-specific profile requires evidence before launch |
| U075 | Custom expedition vehicle | Vehicle | Dimensions, capacity, configuration | A catalogue design differs from physical stock |
| U076 | Fleet management service | Service | Provider, fleet scope, coverage | Service does not own every managed vehicle |
| U077 | Heavy transport service | Service | Provider, capabilities, region | Permits and route facts are job-specific |
| U078 | Cold-chain logistics | Service | Provider, temperature range, coverage | Claims/certifications scoped correctly |
| U079 | Warehouse outsourcing | Service | Provider, capabilities, locations | Warehouse place distinct from service |
| U080 | Rail infrastructure maintenance | Service | Provider, scope, region | Equipment and delivered cases remain separate |

## Places, hospitality and event-related offers

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U081 | Conference venue hire | Service | Provider, venue, capacity context | Venue Place reused by events |
| U082 | Trade-fair booth construction | Service | Provider, scope, relevant cases | Contractor is not fair organizer |
| U083 | Corporate event production | Service | Provider, scope, coverage | Produced Event has its own organizer roles |
| U084 | Live expert webinar | Event | Dates, organizer, virtual location | Recording only attached to real occurrence |
| U085 | Industry conference | Event | Dates, organizer, physical venue | Reuse shared location; no footer-address substitution |
| U086 | Private hospital conference | Event | Dates, scientific roles, venue | Host organization versus scientific lead |
| U087 | Retreat venue hire | Service | Provider, place, scope | Actual hospitality business can be separately typed |
| U088 | Coworking enterprise tenancy | Service | Provider, location, facilities | Office Place and commercial offer separate |
| U089 | Film production location hire | Service | Provider, place, conditions | A location is not automatically a studio business |
| U090 | Showroom consultation | Service | Provider, place, appointment route | Supporting showroom may simply be Place |

## Specialist commissioned and institutional offerings

| ID | Use case | Candidate public type | Useful facts | Main boundary |
| --- | --- | --- | --- | --- |
| U091 | Custom exhibition installation | Product | Materials, dimensions, commissioning scope | Design/build services may be separate |
| U092 | Museum exhibition design | Service | Provider, scope, cases | Exhibition occurrence and creative output distinct |
| U093 | Scientific research contract | Service | Provider, methodology scope | Reports are outputs, not separate services |
| U094 | Materials testing engagement | Service | Provider, methods, evidence | Test report versus certification |
| U095 | Certification assessment service | Service | Provider, scope, authority evidence | Service provider and issued certificate separate |
| U096 | Technical publishing commission | Service | Provider, formats, scope | Book/issue support still needs real examples |
| U097 | Specialist information subscription | Service | Provider, publication scope, access terms | Not an ordinary shop edition; publication identity separate |
| U098 | Custom furniture for buildings | Product | Materials, dimensions, configuration | Exclude ordinary retail catalogue stock |
| U099 | Public art commission | Service | Creator/provider, scope, examples | Resulting artwork requires separate agreed type mapping |
| U100 | Industrial chemical formulation | Product | Composition scope, application, technical facts | Not an ordinary shopping SKU; safety claims source-owned |

## What this tests in the extension model

These examples need shared identity, localized text/homes, specifications, responsible organizations/people, places, manually maintained offers, recognition/certification and content relationships. They do not establish a need for 100 isolated implementations. Many providers additionally need Organization/LocalBusiness or medical profiles; those describe the provider, not the offering itself.

The proposed core must be broad enough to represent all these families manually, while developer adapters connect specialist sources. Specific native properties still need a reviewed type/property matrix before release. Books, periodicals and specialized vehicles remain evidence gaps, not forgotten requirements. This catalogue does not infer support for their full domain models.

Vocabulary references: [Product](https://schema.org/Product), [ProductModel](https://schema.org/ProductModel), [Service](https://schema.org/Service), [SoftwareApplication](https://schema.org/SoftwareApplication), [Place](https://schema.org/Place), [House](https://schema.org/House), [Residence](https://schema.org/Residence), [Vehicle](https://schema.org/Vehicle), [Event](https://schema.org/Event).
