<?php
/**
 * Add Arabic Translations for Existing Diagnoses
 * 
 * This script adds Arabic translations for existing English diagnosis entries
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function snks_add_arabic_diagnoses() {
	global $wpdb;
	
	// Start transaction
	$wpdb->query( 'START TRANSACTION' );
	
	try {
		$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
		
		// Get all existing diagnoses
		$existing_diagnoses = $wpdb->get_results( "SELECT * FROM {$diagnoses_table}" );
		
		// Common mental health diagnoses with Arabic translations
		$diagnosis_translations = array(
			// Anxiety Disorders (both singular and plural)
			'Anxiety Disorder' => array(
				'ar' => 'اضطراب القلق',
				'description_en' => 'A mental health disorder characterized by feelings of worry, anxiety, or fear that are strong enough to interfere with daily activities.',
				'description_ar' => 'اضطراب صحي نفسي يتميز بمشاعر القلق والخوف التي تكون قوية بما يكفي للتدخل في الأنشطة اليومية.'
			),
			'Anxiety Disorders' => array(
				'ar' => 'اضطرابات القلق',
				'description_en' => 'A group of mental health disorders characterized by feelings of worry, anxiety, or fear that are strong enough to interfere with daily activities.',
				'description_ar' => 'مجموعة من الاضطرابات الصحية النفسية تتميز بمشاعر القلق والخوف التي تكون قوية بما يكفي للتدخل في الأنشطة اليومية.'
			),
			'Generalized Anxiety Disorder' => array(
				'ar' => 'اضطراب القلق العام',
				'description_en' => 'Excessive anxiety and worry about various aspects of life, occurring more days than not for at least 6 months.',
				'description_ar' => 'قلق واهتمام مفرط حول جوانب مختلفة من الحياة، يحدث في معظم الأيام لمدة 6 أشهر على الأقل.'
			),
			'Panic Disorder' => array(
				'ar' => 'اضطراب الهلع',
				'description_en' => 'Recurrent unexpected panic attacks followed by persistent concern about having additional attacks.',
				'description_ar' => 'نوبات هلع متكررة وغير متوقعة يتبعها قلق مستمر حول حدوث نوبات إضافية.'
			),
			'Social Anxiety Disorder' => array(
				'ar' => 'اضطراب القلق الاجتماعي',
				'description_en' => 'Intense fear of social situations and being judged or embarrassed in front of others.',
				'description_ar' => 'خوف شديد من المواقف الاجتماعية والخوف من الحكم أو الإحراج أمام الآخرين.'
			),
			
			// Mood Disorders
			'Depression' => array(
				'ar' => 'الاكتئاب',
				'description_en' => 'A mood disorder that causes persistent feelings of sadness and loss of interest in activities.',
				'description_ar' => 'اضطراب مزاجي يسبب مشاعر حزن مستمرة وفقدان الاهتمام بالأنشطة.'
			),
			'Major Depressive Disorder' => array(
				'ar' => 'الاضطراب الاكتئابي الجسيم',
				'description_en' => 'A serious mental health condition characterized by persistent low mood and loss of interest in activities.',
				'description_ar' => 'حالة صحية نفسية خطيرة تتميز بمزاج منخفض مستمر وفقدان الاهتمام بالأنشطة.'
			),
			'Bipolar Disorder' => array(
				'ar' => 'الاضطراب ثنائي القطب',
				'description_en' => 'A mental health condition characterized by extreme mood swings including emotional highs and lows.',
				'description_ar' => 'حالة صحية نفسية تتميز بتقلبات مزاجية شديدة تشمل ارتفاعات وانخفاضات عاطفية.'
			),
			'Persistent Depressive Disorder' => array(
				'ar' => 'الاضطراب الاكتئابي المستمر',
				'description_en' => 'A long-term form of depression that lasts for at least two years.',
				'description_ar' => 'شكل طويل الأمد من الاكتئاب يستمر لمدة عامين على الأقل.'
			),
			
			// Trauma and Stress Disorders
			'Post-Traumatic Stress Disorder' => array(
				'ar' => 'اضطراب ما بعد الصدمة',
				'description_en' => 'A mental health condition triggered by experiencing or witnessing a terrifying event.',
				'description_ar' => 'حالة صحية نفسية تنجم عن تجربة أو مشاهدة حدث مرعب.'
			),
			'Acute Stress Disorder' => array(
				'ar' => 'اضطراب الإجهاد الحاد',
				'description_en' => 'A short-term mental health condition that can occur within the first month of exposure to a traumatic event.',
				'description_ar' => 'حالة صحية نفسية قصيرة المدى يمكن أن تحدث خلال الشهر الأول من التعرض لحدث صادم.'
			),
			
			// Obsessive-Compulsive and Related Disorders
			'Obsessive-Compulsive Disorder' => array(
				'ar' => 'اضطراب الوسواس القهري',
				'description_en' => 'A mental health condition characterized by unwanted recurring thoughts and behaviors.',
				'description_ar' => 'حالة صحية نفسية تتميز بأفكار وسلوكيات متكررة وغير مرغوب فيها.'
			),
			
			// Eating Disorders
			'Anorexia Nervosa' => array(
				'ar' => 'فقدان الشهية العصبي',
				'description_en' => 'An eating disorder characterized by an intense fear of gaining weight and a distorted body image.',
				'description_ar' => 'اضطراب في الأكل يتميز بخوف شديد من زيادة الوزن وصورة مشوهة للجسم.'
			),
			'Bulimia Nervosa' => array(
				'ar' => 'الشره العصبي',
				'description_en' => 'An eating disorder characterized by binge eating followed by compensatory behaviors.',
				'description_ar' => 'اضطراب في الأكل يتميز بنوبات الأكل الشره يتبعها سلوكيات تعويضية.'
			),
			'Binge Eating Disorder' => array(
				'ar' => 'اضطراب الأكل الشره',
				'description_en' => 'An eating disorder characterized by recurrent episodes of eating large amounts of food.',
				'description_ar' => 'اضطراب في الأكل يتميز بنوبات متكررة من تناول كميات كبيرة من الطعام.'
			),
			
			// Personality Disorders
			'Borderline Personality Disorder' => array(
				'ar' => 'اضطراب الشخصية الحدية',
				'description_en' => 'A mental health condition characterized by unstable moods, behavior, and relationships.',
				'description_ar' => 'حالة صحية نفسية تتميز بتقلبات مزاجية وسلوكية وعلاقات غير مستقرة.'
			),
			'Narcissistic Personality Disorder' => array(
				'ar' => 'اضطراب الشخصية النرجسية',
				'description_en' => 'A mental health condition characterized by an inflated sense of self-importance and a deep need for admiration.',
				'description_ar' => 'حالة صحية نفسية تتميز بإحساس مبالغ فيه بأهمية الذات وحاجة عميقة للإعجاب.'
			),
			
			// Substance Use Disorders
			'Substance Use Disorder' => array(
				'ar' => 'اضطراب استخدام المواد',
				'description_en' => 'A mental health condition characterized by the continued use of substances despite harmful consequences.',
				'description_ar' => 'حالة صحية نفسية تتميز بالاستمرار في استخدام المواد رغم العواقب الضارة.'
			),
			'Alcohol Use Disorder' => array(
				'ar' => 'اضطراب استخدام الكحول',
				'description_en' => 'A pattern of alcohol use that involves problems controlling drinking and continued use despite problems.',
				'description_ar' => 'نمط من استخدام الكحول يتضمن مشاكل في التحكم في الشرب والاستمرار في الاستخدام رغم المشاكل.'
			),
			
			// Sleep Disorders
			'Insomnia' => array(
				'ar' => 'الأرق',
				'description_en' => 'A sleep disorder characterized by difficulty falling asleep, staying asleep, or getting quality sleep.',
				'description_ar' => 'اضطراب في النوم يتميز بصعوبة في النوم أو البقاء نائماً أو الحصول على نوم جيد.'
			),
			'Sleep Apnea' => array(
				'ar' => 'انقطاع النفس أثناء النوم',
				'description_en' => 'A serious sleep disorder where breathing repeatedly stops and starts during sleep.',
				'description_ar' => 'اضطراب خطير في النوم حيث يتوقف التنفس ويبدأ بشكل متكرر أثناء النوم.'
			),
			
			// Other Common Conditions
			'Stress' => array(
				'ar' => 'التوتر',
				'description_en' => 'A state of mental or emotional strain resulting from demanding circumstances.',
				'description_ar' => 'حالة من الإجهاد العقلي أو العاطفي ناتجة عن ظروف متطلبة.'
			),
			'Burnout' => array(
				'ar' => 'الإرهاق المهني',
				'description_en' => 'A state of emotional, physical, and mental exhaustion caused by excessive and prolonged stress.',
				'description_ar' => 'حالة من الإرهاق العاطفي والجسدي والعقلي ناتجة عن التوتر المفرط والمطول.'
			),
			'Grief' => array(
				'ar' => 'الحزن',
				'description_en' => 'A natural response to loss, particularly the loss of someone or something important.',
				'description_ar' => 'استجابة طبيعية للخسارة، خاصة فقدان شخص أو شيء مهم.'
			),
			'Low Self-Esteem' => array(
				'ar' => 'انخفاض تقدير الذات',
				'description_en' => 'A negative evaluation of oneself and one\'s abilities.',
				'description_ar' => 'تقييم سلبي للذات وقدراتها.'
			),
			'Relationship Issues' => array(
				'ar' => 'مشاكل العلاقات',
				'description_en' => 'Difficulties in interpersonal relationships that cause emotional distress.',
				'description_ar' => 'صعوبات في العلاقات الشخصية تسبب ضائقة عاطفية.'
			),
			'Work-Related Stress' => array(
				'ar' => 'التوتر المرتبط بالعمل',
				'description_en' => 'Stress and anxiety related to work environment, responsibilities, or job performance.',
				'description_ar' => 'التوتر والقلق المرتبط ببيئة العمل أو المسؤوليات أو الأداء الوظيفي.'
			),
			'Parenting Stress' => array(
				'ar' => 'توتر الأبوة والأمومة',
				'description_en' => 'Stress and anxiety related to parenting responsibilities and challenges.',
				'description_ar' => 'التوتر والقلق المرتبط بمسؤوليات وتحديات الأبوة والأمومة.'
			),
			'Academic Stress' => array(
				'ar' => 'التوتر الأكاديمي',
				'description_en' => 'Stress and anxiety related to academic performance, exams, and educational responsibilities.',
				'description_ar' => 'التوتر والقلق المرتبط بالأداء الأكاديمي والامتحانات والمسؤوليات التعليمية.'
			),
			'Financial Stress' => array(
				'ar' => 'التوتر المالي',
				'description_en' => 'Stress and anxiety related to financial concerns, debt, or economic insecurity.',
				'description_ar' => 'التوتر والقلق المرتبط بالاهتمامات المالية أو الديون أو عدم الأمان الاقتصادي.'
			),
			'Health Anxiety' => array(
				'ar' => 'قلق الصحة',
				'description_en' => 'Excessive worry about health and fear of having a serious medical condition.',
				'description_ar' => 'قلق مفرط حول الصحة والخوف من الإصابة بحالة طبية خطيرة.'
			),
			'Phobias' => array(
				'ar' => 'الرهاب',
				'description_en' => 'Intense, irrational fear of specific objects, situations, or activities.',
				'description_ar' => 'خوف شديد وغير عقلاني من أشياء أو مواقف أو أنشطة محددة.'
			),
			'Adjustment Disorder' => array(
				'ar' => 'اضطراب التكيف',
				'description_en' => 'An emotional or behavioral reaction to a stressful event or change in a person\'s life.',
				'description_ar' => 'رد فعل عاطفي أو سلوكي لحدث مرهق أو تغيير في حياة الشخص.'
			),
			
			// Additional diagnoses found in database
			'Stress Management' => array(
				'ar' => 'إدارة التوتر',
				'description_en' => 'Techniques and strategies for managing and reducing stress levels.',
				'description_ar' => 'تقنيات واستراتيجيات لإدارة وتقليل مستويات التوتر.'
			),
			'Trauma and PTSD' => array(
				'ar' => 'الصدمة واضطراب ما بعد الصدمة',
				'description_en' => 'Treatment for traumatic experiences and post-traumatic stress disorder.',
				'description_ar' => 'علاج التجارب الصادمة واضطراب ما بعد الصدمة.'
			),
			'Addiction' => array(
				'ar' => 'الإدمان',
				'description_en' => 'Treatment for substance abuse and behavioral addictions.',
				'description_ar' => 'علاج إساءة استخدام المواد والإدمان السلوكي.'
			),
			'Eating Disorders' => array(
				'ar' => 'اضطرابات الأكل',
				'description_en' => 'Treatment for various eating disorders including anorexia, bulimia, and binge eating.',
				'description_ar' => 'علاج اضطرابات الأكل المختلفة بما في ذلك فقدان الشهية والشره العصبي والأكل الشره.'
			),
			'Sleep Disorders' => array(
				'ar' => 'اضطرابات النوم',
				'description_en' => 'Treatment for various sleep-related problems and disorders.',
				'description_ar' => 'علاج مشاكل واضطرابات النوم المختلفة.'
			),
			'Grief and Loss' => array(
				'ar' => 'الحزن والخسارة',
				'description_en' => 'Support for dealing with grief, loss, and bereavement.',
				'description_ar' => 'دعم للتعامل مع الحزن والخسارة والفقدان.'
			),
			'Self-Esteem Issues' => array(
				'ar' => 'مشاكل تقدير الذات',
				'description_en' => 'Treatment for low self-esteem and self-confidence issues.',
				'description_ar' => 'علاج انخفاض تقدير الذات ومشاكل الثقة بالنفس.'
			),
			'Work-Life Balance' => array(
				'ar' => 'التوازن بين العمل والحياة',
				'description_en' => 'Support for achieving balance between work and personal life.',
				'description_ar' => 'دعم لتحقيق التوازن بين العمل والحياة الشخصية.'
			),
			'Family Therapy' => array(
				'ar' => 'العلاج الأسري',
				'description_en' => 'Therapy focused on family dynamics and relationships.',
				'description_ar' => 'علاج يركز على ديناميكيات العلاقات الأسرية.'
			),
			'Couples Counseling' => array(
				'ar' => 'استشارات الأزواج',
				'description_en' => 'Counseling for couples to improve their relationship.',
				'description_ar' => 'استشارات للأزواج لتحسين علاقتهم.'
			),
			'Child and Adolescent Therapy' => array(
				'ar' => 'علاج الأطفال والمراهقين',
				'description_en' => 'Specialized therapy for children and teenagers.',
				'description_ar' => 'علاج متخصص للأطفال والمراهقين.'
			),
			'Anger Management' => array(
				'ar' => 'إدارة الغضب',
				'description_en' => 'Techniques for managing and controlling anger.',
				'description_ar' => 'تقنيات لإدارة والتحكم في الغضب.'
			),
			'OCD (Obsessive-Compulsive Disorder)' => array(
				'ar' => 'اضطراب الوسواس القهري',
				'description_en' => 'Treatment for obsessive-compulsive disorder.',
				'description_ar' => 'علاج اضطراب الوسواس القهري.'
			),
			'Personality Disorders' => array(
				'ar' => 'اضطرابات الشخصية',
				'description_en' => 'Treatment for various personality disorders.',
				'description_ar' => 'علاج اضطرابات الشخصية المختلفة.'
			),
			'Panic Disorders' => array(
				'ar' => 'اضطرابات الهلع',
				'description_en' => 'Treatment for panic disorders and panic attacks.',
				'description_ar' => 'علاج اضطرابات الهلع ونوبات الهلع.'
			),
			
			// Rare Neurological Conditions (Based on the image content)
			'Pyridoxine-Dependent Epilepsy' => array(
				'ar' => 'الصرع المعتمد على البيريدوكسين',
				'description_en' => 'A rare form of epilepsy that manifests from the first days of birth, linked to an enzyme deficiency affecting the body\'s utilization of Vitamin B6.',
				'description_ar' => 'نوع نادر من الصرع يظهر من الأيام الأولى للولادة، مرتبط بنقص إنزيمي يؤثر على استفادة الجسم من فيتامين ب6.'
			),
			'Rare Epilepsy' => array(
				'ar' => 'الصرع النادر',
				'description_en' => 'Uncommon forms of epilepsy that require specialized diagnosis and treatment approaches.',
				'description_ar' => 'أشكال غير شائعة من الصرع تتطلب تشخيص وعلاج متخصص.'
			),
			'Genetic Epilepsy' => array(
				'ar' => 'الصرع الوراثي',
				'description_en' => 'Epilepsy caused by genetic mutations or inherited conditions affecting brain function.',
				'description_ar' => 'صرع ناتج عن طفرات جينية أو حالات وراثية تؤثر على وظائف المخ.'
			),
			'Metabolic Disorders' => array(
				'ar' => 'اضطرابات التمثيل الغذائي',
				'description_en' => 'Conditions affecting the body\'s ability to process and use nutrients, often requiring specialized dietary management.',
				'description_ar' => 'حالات تؤثر على قدرة الجسم على معالجة واستخدام العناصر الغذائية، غالباً ما تتطلب إدارة غذائية متخصصة.'
			),
			'Vitamin Deficiency Disorders' => array(
				'ar' => 'اضطرابات نقص الفيتامينات',
				'description_en' => 'Medical conditions caused by insufficient levels of essential vitamins, requiring specific supplementation.',
				'description_ar' => 'حالات طبية ناتجة عن مستويات غير كافية من الفيتامينات الأساسية، تتطلب مكملات محددة.'
			),
			'Sleep Movement Disorders' => array(
				'ar' => 'اضطرابات حركة النوم',
				'description_en' => 'Conditions characterized by abnormal movements during sleep, including sleep attacks and nocturnal movements.',
				'description_ar' => 'حالات تتميز بحركات غير طبيعية أثناء النوم، تشمل نوبات النوم والحركات الليلية.'
			),
			'Neurological Disorders' => array(
				'ar' => 'الاضطرابات العصبية',
				'description_en' => 'Conditions affecting the nervous system, including brain, spinal cord, and nerve disorders.',
				'description_ar' => 'حالات تؤثر على الجهاز العصبي، تشمل اضطرابات المخ والحبل الشوكي والأعصاب.'
			),
			'Rare Diseases' => array(
				'ar' => 'الأمراض النادرة',
				'description_en' => 'Medical conditions that affect a small percentage of the population, often requiring specialized care and treatment.',
				'description_ar' => 'حالات طبية تؤثر على نسبة صغيرة من السكان، غالباً ما تتطلب رعاية وعلاج متخصص.'
			),
			'Childhood Neurological Disorders' => array(
				'ar' => 'الاضطرابات العصبية لدى الأطفال',
				'description_en' => 'Neurological conditions that specifically affect children, requiring specialized pediatric neurological care.',
				'description_ar' => 'حالات عصبية تؤثر بشكل خاص على الأطفال، تتطلب رعاية عصبية أطفال متخصصة.'
			),
			'Developmental Disorders' => array(
				'ar' => 'اضطرابات النمو',
				'description_en' => 'Conditions affecting a child\'s development, including physical, cognitive, and behavioral aspects.',
				'description_ar' => 'حالات تؤثر على نمو الطفل، تشمل الجوانب الجسدية والمعرفية والسلوكية.'
			),
			'Medication Management' => array(
				'ar' => 'إدارة الأدوية',
				'description_en' => 'Specialized care for managing complex medication regimens, especially for rare conditions requiring imported medications.',
				'description_ar' => 'رعاية متخصصة لإدارة أنظمة الأدوية المعقدة، خاصة للحالات النادرة التي تتطلب أدوية مستوردة.'
			),
			'Specialized Medical Care' => array(
				'ar' => 'الرعاية الطبية المتخصصة',
				'description_en' => 'Medical care for rare or complex conditions requiring specialized knowledge and treatment approaches.',
				'description_ar' => 'رعاية طبية للحالات النادرة أو المعقدة التي تتطلب معرفة وعلاج متخصص.'
			),
			'Second Opinion Consultation' => array(
				'ar' => 'استشارة الرأي الثاني',
				'description_en' => 'Seeking additional medical opinions for complex or rare conditions to ensure proper diagnosis and treatment.',
				'description_ar' => 'البحث عن آراء طبية إضافية للحالات المعقدة أو النادرة لضمان التشخيص والعلاج الصحيح.'
			),
			'International Medical Consultation' => array(
				'ar' => 'الاستشارة الطبية الدولية',
				'description_en' => 'Consulting with medical professionals internationally for rare conditions or specialized treatments.',
				'description_ar' => 'استشارة المتخصصين الطبيين دولياً للحالات النادرة أو العلاجات المتخصصة.'
			),
			'Medical Import Coordination' => array(
				'ar' => 'تنسيق استيراد الأدوية',
				'description_en' => 'Assistance with importing specialized medications and treatments not available locally.',
				'description_ar' => 'المساعدة في استيراد الأدوية والعلاجات المتخصصة غير المتوفرة محلياً.'
			),
			'Rare Disease Support' => array(
				'ar' => 'دعم الأمراض النادرة',
				'description_en' => 'Support services for patients and families dealing with rare medical conditions.',
				'description_ar' => 'خدمات الدعم للمرضى والعائلات التي تتعامل مع الحالات الطبية النادرة.'
			),
			'Parent Support Groups' => array(
				'ar' => 'مجموعات دعم الآباء',
				'description_en' => 'Support groups for parents of children with rare or chronic medical conditions.',
				'description_ar' => 'مجموعات دعم للآباء الذين لديهم أطفال بحالات طبية نادرة أو مزمنة.'
			),
			'Medical Research Coordination' => array(
				'ar' => 'تنسيق البحث الطبي',
				'description_en' => 'Assistance in connecting with medical research programs and clinical trials for rare conditions.',
				'description_ar' => 'المساعدة في التواصل مع برامج البحث الطبي والتجارب السريرية للحالات النادرة.'
			),
			
			// Specific Medical Terms from the Image
			'Epilepsy' => array(
				'ar' => 'الصرع',
				'description_en' => 'A neurological disorder characterized by recurrent seizures.',
				'description_ar' => 'اضطراب عصبي يتميز بنوبات متكررة.'
			),
			'Seizures' => array(
				'ar' => 'النوبات',
				'description_en' => 'Sudden, uncontrolled electrical disturbances in the brain.',
				'description_ar' => 'اضطرابات كهربائية مفاجئة وغير منضبطة في المخ.'
			),
			'Sleep Attacks' => array(
				'ar' => 'نوبات النوم',
				'description_en' => 'Sudden episodes of sleep or sleep-like behavior during wakeful hours.',
				'description_ar' => 'نوبات مفاجئة من النوم أو السلوك الشبيه بالنوم أثناء ساعات اليقظة.'
			),
			'Nocturnal Movements' => array(
				'ar' => 'الحركات الليلية',
				'description_en' => 'Abnormal movements that occur during sleep or at night.',
				'description_ar' => 'حركات غير طبيعية تحدث أثناء النوم أو في الليل.'
			),
			'Enzyme Deficiency' => array(
				'ar' => 'نقص الإنزيم',
				'description_en' => 'A condition where the body lacks sufficient amounts of specific enzymes needed for normal function.',
				'description_ar' => 'حالة يفتقر فيها الجسم لكميات كافية من إنزيمات محددة مطلوبة للوظيفة الطبيعية.'
			),
			'Vitamin B6 Deficiency' => array(
				'ar' => 'نقص فيتامين ب6',
				'description_en' => 'Insufficient levels of Vitamin B6 in the body, affecting various metabolic processes.',
				'description_ar' => 'مستويات غير كافية من فيتامين ب6 في الجسم، تؤثر على عمليات التمثيل الغذائي المختلفة.'
			),
			'Anti-Epilepsy Medications' => array(
				'ar' => 'الأدوية المضادة للصرع',
				'description_en' => 'Medications used to control seizures and epilepsy, which may not be suitable for all types of epilepsy.',
				'description_ar' => 'أدوية تستخدم للتحكم في النوبات والصرع، قد لا تكون مناسبة لجميع أنواع الصرع.'
			),
			'Medication Depletion' => array(
				'ar' => 'استنزاف الأدوية',
				'description_en' => 'When medications cause the body to lose essential nutrients or vitamins.',
				'description_ar' => 'عندما تسبب الأدوية فقدان الجسم للعناصر الغذائية أو الفيتامينات الأساسية.'
			),
			'Misdiagnosis' => array(
				'ar' => 'التشخيص الخاطئ',
				'description_en' => 'Incorrect diagnosis of a medical condition, which can lead to inappropriate treatment.',
				'description_ar' => 'تشخيص خاطئ لحالة طبية، يمكن أن يؤدي إلى علاج غير مناسب.'
			),
			'Rare Disease Diagnosis' => array(
				'ar' => 'تشخيص الأمراض النادرة',
				'description_en' => 'The process of identifying rare medical conditions that affect very few people.',
				'description_ar' => 'عملية تحديد الحالات الطبية النادرة التي تؤثر على عدد قليل جداً من الناس.'
			),
			'Specialized Pediatric Care' => array(
				'ar' => 'الرعاية المتخصصة للأطفال',
				'description_en' => 'Medical care specifically designed for children with complex or rare conditions.',
				'description_ar' => 'رعاية طبية مصممة خصيصاً للأطفال ذوي الحالات المعقدة أو النادرة.'
			),
			'Neurological Consultation' => array(
				'ar' => 'استشارة عصبية',
				'description_en' => 'Specialized consultation with neurologists for brain and nervous system disorders.',
				'description_ar' => 'استشارة متخصصة مع أطباء الأعصاب لاضطرابات المخ والجهاز العصبي.'
			),
			'Pediatric Neurology' => array(
				'ar' => 'طب أعصاب الأطفال',
				'description_en' => 'Medical specialty focusing on neurological disorders in children.',
				'description_ar' => 'تخصص طبي يركز على الاضطرابات العصبية لدى الأطفال.'
			),
			'Medical Import Services' => array(
				'ar' => 'خدمات استيراد الأدوية',
				'description_en' => 'Services to help import medications and treatments not available locally.',
				'description_ar' => 'خدمات للمساعدة في استيراد الأدوية والعلاجات غير المتوفرة محلياً.'
			),
			'International Medical Networks' => array(
				'ar' => 'الشبكات الطبية الدولية',
				'description_en' => 'Connections with medical professionals and institutions worldwide for rare conditions.',
				'description_ar' => 'روابط مع المتخصصين الطبيين والمؤسسات في جميع أنحاء العالم للحالات النادرة.'
			),
			'Rare Disease Communities' => array(
				'ar' => 'مجتمعات الأمراض النادرة',
				'description_en' => 'Online and offline communities of patients and families dealing with rare conditions.',
				'description_ar' => 'مجتمعات عبر الإنترنت وخارجها للمرضى والعائلات التي تتعامل مع الحالات النادرة.'
			),
			'Medical Documentation' => array(
				'ar' => 'التوثيق الطبي',
				'description_en' => 'Comprehensive medical records and documentation for rare conditions.',
				'description_ar' => 'سجلات طبية شاملة وتوثيق للحالات النادرة.'
			),
			'X-Ray Analysis' => array(
				'ar' => 'تحليل الأشعة السينية',
				'description_en' => 'Medical imaging analysis for diagnostic purposes.',
				'description_ar' => 'تحليل التصوير الطبي لأغراض التشخيص.'
			),
			'Medical Test Results' => array(
				'ar' => 'نتائج الفحوصات الطبية',
				'description_en' => 'Results from various medical tests and laboratory analyses.',
				'description_ar' => 'نتائج من فحوصات طبية مختلفة وتحليلات المختبر.'
			),
			'Treatment Monitoring' => array(
				'ar' => 'مراقبة العلاج',
				'description_en' => 'Ongoing monitoring of treatment effectiveness and patient response.',
				'description_ar' => 'مراقبة مستمرة لفعالية العلاج واستجابة المريض.'
			),
			'Medication Side Effects' => array(
				'ar' => 'الآثار الجانبية للأدوية',
				'description_en' => 'Unwanted effects of medications, including nutrient depletion.',
				'description_ar' => 'آثار غير مرغوب فيها للأدوية، تشمل استنزاف العناصر الغذائية.'
			),
			'Vitamin Supplementation' => array(
				'ar' => 'المكملات الفيتامينية',
				'description_en' => 'Providing essential vitamins to address deficiencies or support treatment.',
				'description_ar' => 'توفير الفيتامينات الأساسية لمعالجة النقص أو دعم العلاج.'
			),
			'Genetic Testing' => array(
				'ar' => 'الفحص الجيني',
				'description_en' => 'Medical tests to identify genetic mutations or inherited conditions.',
				'description_ar' => 'فحوصات طبية لتحديد الطفرات الجينية أو الحالات الوراثية.'
			),
			'Metabolic Testing' => array(
				'ar' => 'فحص التمثيل الغذائي',
				'description_en' => 'Tests to evaluate the body\'s metabolic processes and enzyme function.',
				'description_ar' => 'فحوصات لتقييم عمليات التمثيل الغذائي في الجسم ووظيفة الإنزيمات.'
			),
			'Clinical Trials' => array(
				'ar' => 'التجارب السريرية',
				'description_en' => 'Research studies to test new treatments for rare conditions.',
				'description_ar' => 'دراسات بحثية لاختبار علاجات جديدة للحالات النادرة.'
			),
			'Medical Research Programs' => array(
				'ar' => 'برامج البحث الطبي',
				'description_en' => 'Research programs focused on understanding and treating rare diseases.',
				'description_ar' => 'برامج بحثية تركز على فهم وعلاج الأمراض النادرة.'
			),
			'Patient Advocacy' => array(
				'ar' => 'الدفاع عن المرضى',
				'description_en' => 'Supporting patients and families in navigating complex medical systems.',
				'description_ar' => 'دعم المرضى والعائلات في التنقل في الأنظمة الطبية المعقدة.'
			),
			'Medical Second Opinions' => array(
				'ar' => 'الآراء الطبية الثانية',
				'description_en' => 'Seeking additional medical opinions for complex or rare conditions.',
				'description_ar' => 'البحث عن آراء طبية إضافية للحالات المعقدة أو النادرة.'
			),
			'Specialist Referrals' => array(
				'ar' => 'الإحالات للمتخصصين',
				'description_en' => 'Connecting patients with specialists who have experience with rare conditions.',
				'description_ar' => 'ربط المرضى مع المتخصصين الذين لديهم خبرة مع الحالات النادرة.'
			),
			'Medical Case Management' => array(
				'ar' => 'إدارة الحالات الطبية',
				'description_en' => 'Coordinating comprehensive care for complex medical cases.',
				'description_ar' => 'تنسيق الرعاية الشاملة للحالات الطبية المعقدة.'
			),
			'Family Medical Support' => array(
				'ar' => 'الدعم الطبي للعائلة',
				'description_en' => 'Support services for families dealing with complex medical conditions.',
				'description_ar' => 'خدمات الدعم للعائلات التي تتعامل مع الحالات الطبية المعقدة.'
			),
			'Medical Education' => array(
				'ar' => 'التعليم الطبي',
				'description_en' => 'Educational resources for patients and families about rare conditions.',
				'description_ar' => 'موارد تعليمية للمرضى والعائلات حول الحالات النادرة.'
			),
			'Treatment Planning' => array(
				'ar' => 'تخطيط العلاج',
				'description_en' => 'Developing comprehensive treatment plans for complex medical conditions.',
				'description_ar' => 'تطوير خطط علاج شاملة للحالات الطبية المعقدة.'
			),
			'Medication Coordination' => array(
				'ar' => 'تنسيق الأدوية',
				'description_en' => 'Coordinating complex medication regimens and monitoring interactions.',
				'description_ar' => 'تنسيق أنظمة الأدوية المعقدة ومراقبة التفاعلات.'
			),
			'Medical Follow-up' => array(
				'ar' => 'المتابعة الطبية',
				'description_en' => 'Ongoing medical monitoring and follow-up care for chronic conditions.',
				'description_ar' => 'المراقبة الطبية المستمرة ورعاية المتابعة للحالات المزمنة.'
			)
		);
		
		$updated_count = 0;
		$added_count = 0;
		
		// Update existing diagnoses with Arabic translations
		foreach ( $existing_diagnoses as $diagnosis ) {
			$english_name = $diagnosis->name_en ?: $diagnosis->name;
			
			if ( isset( $diagnosis_translations[$english_name] ) ) {
				$translation = $diagnosis_translations[$english_name];
				
				$wpdb->update(
					$diagnoses_table,
					array(
						'name_ar' => $translation['ar'],
						'description_ar' => $translation['description_ar']
					),
					array( 'id' => $diagnosis->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
				
				$updated_count++;
			}
		}
		
		// Add new diagnoses that don't exist yet
		foreach ( $diagnosis_translations as $english_name => $translation ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$diagnoses_table} WHERE name_en = %s OR name = %s",
				$english_name,
				$english_name
			) );
			
			if ( ! $exists ) {
				$wpdb->insert(
					$diagnoses_table,
					array(
						'name_en' => $english_name,
						'name_ar' => $translation['ar'],
						'description_en' => $translation['description_en'],
						'description_ar' => $translation['description_ar']
					),
					array( '%s', '%s', '%s', '%s' )
				);
				
				$added_count++;
			}
		}
		
		// Commit transaction
		$wpdb->query( 'COMMIT' );
		
		return array(
			'success' => true,
			'message' => "Arabic translations added successfully! Updated: {$updated_count} diagnoses, Added: {$added_count} new diagnoses.",
			'updated' => $updated_count,
			'added' => $added_count
		);
		
	} catch ( Exception $e ) {
		// Rollback transaction
		$wpdb->query( 'ROLLBACK' );
		
		return array(
			'success' => false,
			'message' => 'Failed to add Arabic translations: ' . $e->getMessage()
		);
	}
}

// Note: Menu registration is now handled in ai-admin-enhanced.php

function snks_arabic_diagnoses_page() {
	// Load admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	
	// Handle form submission
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'add_arabic_diagnoses' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'add_arabic_diagnoses' ) ) {
			$result = snks_add_arabic_diagnoses();
			
			if ( $result['success'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		}
	}
	
	// Get current diagnoses count
	global $wpdb;
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	$total_diagnoses = $wpdb->get_var( "SELECT COUNT(*) FROM {$diagnoses_table}" );
	$arabic_diagnoses = $wpdb->get_var( "SELECT COUNT(*) FROM {$diagnoses_table} WHERE name_ar IS NOT NULL AND name_ar != ''" );
	?>
	<div class="wrap">
		<h1>Add Arabic Diagnoses</h1>
		
		<div class="card">
			<h2>Current Status</h2>
			<p><strong>Total Diagnoses:</strong> <?php echo esc_html( $total_diagnoses ); ?></p>
			<p><strong>Diagnoses with Arabic:</strong> <?php echo esc_html( $arabic_diagnoses ); ?></p>
			<p><strong>Diagnoses needing Arabic:</strong> <?php echo esc_html( $total_diagnoses - $arabic_diagnoses ); ?></p>
		</div>
		
		<div class="card">
			<h2>Add Arabic Translations</h2>
			<p>This will add Arabic translations for common mental health diagnoses. It will:</p>
			<ul>
				<li>Update existing diagnoses with Arabic translations</li>
				<li>Add new common diagnoses that don't exist yet</li>
				<li>Preserve all existing data</li>
			</ul>
			
			<form method="post">
				<?php wp_nonce_field( 'add_arabic_diagnoses' ); ?>
				<input type="hidden" name="action" value="add_arabic_diagnoses">
				
				<p>
					<button type="submit" class="button button-primary" onclick="return confirm('Are you sure you want to add Arabic translations? This will update existing diagnoses and add new ones.')">
						Add Arabic Translations
					</button>
				</p>
			</form>
		</div>
		
		<div class="card">
			<h2>Diagnoses That Will Be Added/Updated</h2>
			<p>The following diagnoses will be processed:</p>
			<ul>
				<li><strong>Anxiety Disorders:</strong> Generalized Anxiety, Panic Disorder, Social Anxiety</li>
				<li><strong>Mood Disorders:</strong> Depression, Bipolar Disorder, Persistent Depressive Disorder</li>
				<li><strong>Trauma Disorders:</strong> PTSD, Acute Stress Disorder</li>
				<li><strong>Other Conditions:</strong> OCD, Eating Disorders, Personality Disorders</li>
				<li><strong>Common Issues:</strong> Stress, Burnout, Relationship Issues, Work Stress</li>
				<li><strong>Rare Neurological Conditions:</strong> Pyridoxine-Dependent Epilepsy, Rare Epilepsy, Genetic Epilepsy</li>
				<li><strong>Epilepsy & Seizures:</strong> Epilepsy, Seizures, Sleep Attacks, Nocturnal Movements</li>
				<li><strong>Medical Terms:</strong> Enzyme Deficiency, Vitamin B6 Deficiency, Anti-Epilepsy Medications</li>
				<li><strong>Diagnostic Services:</strong> Misdiagnosis, Rare Disease Diagnosis, Genetic Testing, Metabolic Testing</li>
				<li><strong>Specialized Care:</strong> Pediatric Neurology, Neurological Consultation, Specialized Pediatric Care</li>
				<li><strong>Support Services:</strong> Medication Management, International Consultation, Parent Support Groups</li>
				<li><strong>Medical Services:</strong> Medical Import Services, X-Ray Analysis, Medical Documentation</li>
				<li><strong>Treatment Services:</strong> Treatment Monitoring, Medication Coordination, Treatment Planning</li>
				<li><strong>Research & Advocacy:</strong> Clinical Trials, Medical Research Programs, Patient Advocacy</li>
			</ul>
		</div>
	</div>
	<?php
} 