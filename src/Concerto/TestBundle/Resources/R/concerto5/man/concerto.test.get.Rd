\name{leap.test.get}
\alias{leap.test.get}
\title{
    Test object retrieval
}
\description{
    Gets template object.
}
\usage{
    leap.test.get(testId)
}
\arguments{
    \item{testId}{
        Test ID or name.
    }
}
\details{
}
\value{
    List with test object properties:
    \item{id }{test object Id}
    \item{name}{test object name}
    \item{code}{test logic R code}
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    test1 <- leap.test.get(1)
    test2 <- leap.test.get("some_other_test")
}

\keyword{ test }
