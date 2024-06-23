// Step 2: Collect and parse the JSON values
								const values = [];
								$( '.expected-hours-json', parentWrapper.closest( '.accordion-content' ) ).each(
									function () {
										const jsonString = $( this ).val();
										if ( '' !== $( this ).val() ) {
											const parsedValue = JSON.parse( jsonString );
											values.push( parsedValue );
										}

									}
								);
								if ( values.length > 1 ) {
									// Step 1: Flatten the values array into a single array.
									const flattenedArray = [].concat( ...values );
									// Step 2: Count the occurrences of each element.
									const elementCounts = {};
									flattenedArray.forEach(
										function (element) {
											elementCounts[element] = (elementCounts[element] || 0) + 1;
										}
									);
									// Step 3: Filter the elements that occur more than once.
									const elementsWithDuplicates = Object.keys( elementCounts ).filter(
										function (element) {
											return elementCounts[element] > 1;
										}
									);
									console.log( flattenedArray );
								}